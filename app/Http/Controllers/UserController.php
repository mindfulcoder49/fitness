<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(User $user)
    {
        $visitingUser = Auth::user();

        $user->loadCount(['posts', 'comments', 'likes', 'readChangelogs']);

        $posts = $user->posts()->where('is_blog_post', false)->get();
        $comments = $user->comments()->with('post.user')->get();
        $likes = $user->likes()->with('likeable.user')->get();

        $activityFeed = collect()
            ->concat($posts->map(function ($post) {
                $post->activity_type = 'post';
                return $post;
            }))
            ->concat($comments->map(function ($comment) {
                $comment->activity_type = 'comment';
                return $comment;
            }))
            ->concat($likes->map(function ($like) {
                $like->activity_type = 'like';
                return $like;
            }))
            ->sortByDesc('created_at')
            ->take(30)
            ->values();

        $processPost = function ($post) use ($visitingUser) {
            if (!$post || !$post->user) {
                return null;
            }

            $post->loadCount('likes', 'comments');
            $post->load('likes', 'comments.user', 'comments.likes');

            $post->is_liked = $post->likes->contains('user_id', $visitingUser->id);
            $post->can = [
                'delete' => $visitingUser->can('delete', $post),
                'update' => $visitingUser->can('update', $post),
            ];

            $post->comments = $post->comments->filter(function ($comment) {
                return $comment->user;
            })->map(function ($comment) use ($visitingUser) {
                $comment->is_liked = $comment->likes->contains('user_id', $visitingUser->id);
                $comment->can = ['delete' => $visitingUser->can('delete', $comment)];
                return $comment;
            })->values();

            return $post;
        };

        $activityFeed = $activityFeed->map(function ($activity) use ($processPost) {
            if ($activity->activity_type === 'post') {
                return $processPost($activity);
            }
            return $activity;
        })->filter();


        return Inertia::render('Users/Show', [
            'profileUser' => $user,
            'activityFeed' => $activityFeed,
        ]);
    }
}
