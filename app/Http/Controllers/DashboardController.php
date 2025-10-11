<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Changelog;
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

        $readChangelogIds = $user->readChangelogs()->pluck('changelog_id');
        $unreadChangelogs = Changelog::whereNotIn('id', $readChangelogIds)->get();

        $changelogNotifications = $unreadChangelogs->map(function ($changelog) {
            return [
                'id' => 'changelog_' . $changelog->id,
                'type' => 'App Update',
                'description' => 'A new update was released on ' . $changelog->release_date->format('F j, Y'),
                'created_at' => $changelog->release_date,
            ];
        });

        $notifications = [
            'posts' => $postsForNotifications,
            'commentsOnUserPosts' => $commentsOnUserPosts,
            'likesOnUserContent' => $likesOnUserContent,
            'changelogs' => $changelogNotifications,
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

        // 3. Unread changelogs
        foreach ($unreadChangelogs as $changelog) {
            $todos[] = [
                'id' => 'read_changelog_' . $changelog->id,
                'type' => 'Read Update',
                'description' => 'Read the update from ' . $changelog->release_date->format('F j, Y'),
                'changelog_id' => $changelog->id,
            ];
        }

        // 4. Posts not liked
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
            'days_posted' => $user->posts()->reorder()->select(DB::raw('DATE(created_at)'))->distinct()->count(),
            'likes_on_posts' => $user->posts()->withCount('likes')->get()->sum('likes_count'),
            'likes_given' => $user->likes()->count(),
            'total_comments' => $user->comments()->count(),
            'changelogs_read' => $user->readChangelogs()->count(),
        ];

        // Leaderboard data
        $leaderboard = User::withCount(['posts', 'comments', 'likes', 'readChangelogs'])
            ->get()
            ->map(function ($u) {
            $likesOnPosts = $u->posts()->withCount('likes')->get()->sum('likes_count');
            $likesOnComments = $u->comments()->withCount('likes')->get()->sum('likes_count');

            // Count distinct days the user has posted
            $distinctPostDays = $u->posts()
                ->select(DB::raw('DATE(created_at) as post_date'))
                ->distinct()
                ->count();

            // Simple scoring: distinct post days=3, comments=1, likes given=0.5, likes received=1, changelog read=1
            $score = ($distinctPostDays * 3) +
                 ($u->comments_count * 1) +
                 ($u->likes_count * 0.5) +
                 ($likesOnPosts * 1) +
                 ($likesOnComments * 1) +
                 ($u->read_changelogs_count * 0.5);

            return [
                'id' => $u->id,
                'name' => $u->username,
                'score' => round($score),
            ];
            })
            ->sortByDesc('score')
            ->values()
            ->take(20);

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
