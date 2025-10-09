<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $weekAgo = now()->subWeek();

        // Notifications
        $postsForNotifications = Post::with('user:id,username')->where('created_at', '>=', $weekAgo)->where('user_id', '!=', $user->id)->get();
        
        $userPostIds = $user->posts()->pluck('id');
        $commentsOnUserPosts = Comment::with('user:id,username', 'post:id,content')->whereIn('post_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();

        $userCommentIds = $user->comments()->pluck('id');
        $likesOnUserPosts = Like::with('user:id,username')->where('likeable_type', Post::class)->whereIn('likeable_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
        $likesOnUserComments = Like::with('user:id,username')->where('likeable_type', Comment::class)->whereIn('likeable_id', $userCommentIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
        $likesOnUserContent = $likesOnUserPosts->merge($likesOnUserComments);

        $notifications = [
            'posts' => $postsForNotifications,
            'commentsOnUserPosts' => $commentsOnUserPosts,
            'likesOnUserContent' => $likesOnUserContent,
        ];

        // To-Do List Data
        $todos = [];
        // 1. Set daily fitness goal
        if (!$user->daily_fitness_goal) {
            $todos[] = [
                'id' => 'set_goal',
                'type' => 'Set Your Goal',
                'description' => 'Set your daily fitness goal to get started.',
            ];
        }

        // 2. Post daily update
        $hasPostedToday = $user->posts()->whereDate('created_at', today())->exists();
        if (!$hasPostedToday) {
            $todos[] = [
                'id' => 'post_today',
                'type' => 'Daily Update',
                'description' => 'Post your daily fitness update.',
            ];
        }

        // 3. Posts not liked
        $likedPostIds = $user->likes()->where('likeable_type', Post::class)->pluck('likeable_id');
        $postsToLike = Post::with('user:id,username')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('id', $likedPostIds)
            ->where('created_at', '>=', $weekAgo)
            ->latest()
            ->take(5) // Limit to a reasonable number
            ->get()
            ->map(function ($post) {
                return [
                    'id' => 'like_post_' . $post->id,
                    'type' => 'Like a Post',
                    'description' => "Like " . $post->user->username . "'s post.",
                    'post_id' => $post->id,
                ];
            });

        $todos = array_merge($todos, $postsToLike->all());

        // User-specific metrics
        $userMetrics = [
            'total_comments' => $user->comments()->count(),
            'likes_on_comments' => $user->comments()->withCount('likes')->get()->sum('likes_count'),
            'days_posted' => $user->posts()->reorder()->select(DB::raw('DATE(created_at)'))->distinct()->count(),
        ];
        $userMetrics['avg_likes_per_comment'] = $userMetrics['total_comments'] > 0 ? round($userMetrics['likes_on_comments'] / $userMetrics['total_comments'], 2) : 0;

        // Leaderboard data
        $leaderboard = User::withCount(['posts', 'comments', 'likes'])
            ->get()
            ->map(function ($u) {
                $likesOnPosts = $u->posts()->withCount('likes')->get()->sum('likes_count');
                $likesOnComments = $u->comments()->withCount('likes')->get()->sum('likes_count');
                
                // Simple scoring: posts=3, comments=1, likes given=0.5, likes received=1
                $score = ($u->posts_count * 3) +
                         ($u->comments_count * 1) +
                         ($u->likes_count * 0.5) +
                         ($likesOnPosts * 1) +
                         ($likesOnComments * 1);

                return [
                    'id' => $u->id,
                    'name' => $u->username,
                    'score' => round($score),
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take(10);

        $prospectiveHasPostedToday = false;
        if ($user->role === 'prospective') {
            $prospectiveHasPostedToday = $user->posts()->whereDate('created_at', today())->exists();
        }

        $posts = Post::where('is_blog_post', false)
            ->with(['user', 'comments.user', 'comments.likes', 'likes'])
            ->withCount('likes', 'comments')
            ->latest()
            ->get()
            ->map(function ($post) use ($user) {
                // If the post's author has been deleted, skip this post entirely.
                if (!$post->user) {
                    return null;
                }

                $post->is_liked = $post->likes->contains('user_id', $user->id);
                $post->can = ['delete' => $user->can('delete', $post)];

                // Filter out comments from deleted users.
                $post->comments = $post->comments->filter(function ($comment) {
                    return $comment->user;
                })->map(function ($comment) use ($user) {
                    $comment->is_liked = $comment->likes->contains('user_id', $user->id);
                    $comment->can = ['delete' => $user->can('delete', $comment)];
                    return $comment;
                })->values(); // Re-index the comments collection.

                return $post;
            })
            ->filter(); // This removes any `null` posts from the final collection.

        return Inertia::render('Dashboard', [
            'userMetrics' => $userMetrics,
            'leaderboard' => $leaderboard,
            'prospectiveHasPostedToday' => $prospectiveHasPostedToday,
            'posts' => $posts->values(), // Re-index the posts collection.
            'notifications' => $notifications,
            'notificationsLastCheckedAt' => $user->notifications_last_checked_at,
            'todos' => $todos,
        ]);
    }
}
