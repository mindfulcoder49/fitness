<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();

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
                    'name' => $u->name,
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

        $posts = Post::with(['user', 'comments.user', 'comments.likes', 'likes'])
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
        ]);
    }
}
