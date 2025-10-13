<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(User $user)
    {
        $visitingUser = Auth::user();

        // Find common groups
        $visitingUserGroupIds = $visitingUser->groups()->pluck('groups.id');
        $profileUserGroups = $user->groups()->whereIn('groups.id', $visitingUserGroupIds)->get();

        $activityByGroup = $profileUserGroups->mapWithKeys(function ($group) use ($user, $visitingUser) {
            $posts = $user->posts()
                ->with(['user', 'comments.user', 'comments.likes', 'likes'])
                ->withCount('likes', 'comments')
                ->where('group_id', $group->id)
                ->where('is_blog_post', false)
                ->get();

            // Process posts to add required properties for the Post component
            $processedPosts = $posts->map(function ($post) use ($visitingUser) {
                if (!$post || !$post->user) return null;
                $post->is_liked = $post->likes->contains('user_id', $visitingUser->id);
                $post->can = [
                    'delete' => $visitingUser->can('delete', $post),
                    'update' => $visitingUser->can('update', $post),
                ];
                $post->comments = $post->comments->filter(fn ($c) => $c->user)->map(function ($comment) use ($visitingUser) {
                    $comment->is_liked = $comment->likes->contains('user_id', $visitingUser->id);
                    $comment->can = ['delete' => $visitingUser->can('delete', $comment)];
                    return $comment;
                })->values();
                return $post;
            })->filter();

            $comments = $user->comments()->whereHas('post', fn ($q) => $q->where('group_id', $group->id))->with('post.user')->get();
            
            $likes = $user->likes()->whereHasMorph(
                'likeable',
                [Post::class, Comment::class],
                function ($query, $type) use ($group) {
                    if ($type === Post::class) {
                        $query->where('group_id', $group->id);
                    } elseif ($type === Comment::class) {
                        $query->whereHas('post', function ($subQuery) use ($group) {
                            $subQuery->where('group_id', $group->id);
                        });
                    }
                }
            )->with('likeable.user')->get();

            $activityFeed = collect()->concat($processedPosts->map(fn ($p) => $p->setAttribute('activity_type', 'post')))
                ->concat($comments->map(fn ($c) => $c->setAttribute('activity_type', 'comment')))
                ->concat($likes->map(fn ($l) => $l->setAttribute('activity_type', 'like')))
                ->sortByDesc('created_at')->take(20)->values();

            return [$group->id => [
                'group' => $group,
                'activity' => $activityFeed,
            ]];
        });

        return Inertia::render('Users/Show', [
            'profileUser' => $user->loadCount(['posts', 'comments', 'likes']),
            'activityByGroup' => $activityByGroup,
        ]);
    }
}
