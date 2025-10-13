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

        // User's groups
        $groups = $user->groups()->with('creator')->get();

        // Notifications
        $postsForNotifications = Post::with('user:id,username')->where('created_at', '>=', $weekAgo)->where('user_id', '!=', $user->id)->get();
        
        $userPostIds = $user->posts()->where('is_blog_post', false)->pluck('id');
        $commentsOnUserPosts = Comment::with('user:id,username', 'post:id,content')->whereIn('post_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();

        $userCommentIds = $user->comments()->pluck('id');
        $likesOnUserPosts = Like::with(['user:id,username', 'likeable:id,content'])->where('likeable_type', Post::class)->whereIn('likeable_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
        $likesOnUserComments = Like::with(['user:id,username', 'likeable:id,content,post_id'])->where('likeable_type', Comment::class)->whereIn('likeable_id', $userCommentIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
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
            ->latest()
            ->get()
            ->map(function ($post) {
                return [
                    'id' => 'like_post_' . $post->id,
                    'type' => $post->is_blog_post ? 'Like a Blog Post' : 'Like a Post',
                    'description' => "Like " . $post->user->username . "'s post.",
                    'post_id' => $post->id,
                    'content' => $post->content,
                ];
            });

        $todos = array_merge($todos, $postsToLike->all());

        return Inertia::render('Dashboard', [
            'groups' => $groups,
            'notifications' => $notifications,
            'notificationsLastCheckedAt' => $user->notifications_last_checked_at,
            'todos' => $todos,
        ]);
    }
}
