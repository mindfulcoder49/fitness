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
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $weekAgo = now()->subWeek();

        // User's groups
        $groups = $user->groups()->with('creator')->get();
        $userGroupIds = $groups->pluck('id');

        // Notifications
        $postsForNotifications = Post::with('user:id,username')->where('created_at', '>=', $weekAgo)->where('user_id', '!=', $user->id)->whereIn('group_id', $userGroupIds)->get();
        
        $userPostIds = $user->posts()->where('is_blog_post', false)->pluck('id');
        $commentsOnUserPosts = Comment::with(['user:id,username', 'post:id,content,group_id'])->whereIn('post_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();

        $userCommentIds = $user->comments()->pluck('id');
        $likesOnUserPosts = Like::with(['user:id,username', 'likeable:id,content,group_id'])->where('likeable_type', Post::class)->whereIn('likeable_id', $userPostIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
        $likesOnUserComments = Like::with(['user:id,username', 'likeable' => function ($query) {
                $query->with('post:id,group_id');
            }])->where('likeable_type', Comment::class)->whereIn('likeable_id', $userCommentIds)->where('user_id', '!=', $user->id)->where('created_at', '>=', $weekAgo)->get();
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

        // 1. Group tasks to-dos
        Log::info("Dashboard: Fetching group tasks for user ID: {$user->id}");
        $userGroupsWithTasks = $user->groups()->with(['tasks' => function ($query) {
            $query->where('is_current', true);
        }])->get();

        foreach ($userGroupsWithTasks as $group) {
            Log::info("Dashboard: Checking group '{$group->name}' (ID: {$group->id}) for current tasks.");
            if ($group->tasks->isNotEmpty()) {
                Log::info("Dashboard: Found {$group->tasks->count()} current tasks for group ID: {$group->id}", $group->tasks->pluck('id', 'title')->all());
                $postedTaskIdsToday = $user->posts()
                    ->where('group_id', $group->id)
                    ->whereNotNull('group_task_id')
                    ->where('created_at', '>=', now('America/New_York')->startOfDay())
                    ->pluck('group_task_id');

                foreach ($group->tasks as $task) {
                    if (!$postedTaskIdsToday->contains($task->id)) {
                        $newTodo = [
                            'id' => 'post_today_task_' . $task->id,
                            'type' => 'Daily Task',
                            'description' => "{$task->title} ({$group->name})",
                            'group_id' => $group->id,
                        ];
                        $todos[] = $newTodo;
                        Log::info("Dashboard: Added to-do for task ID {$task->id}.", $newTodo);
                    }
                }
            }
        }

        // 2. Unread changelogs
        foreach ($unreadChangelogs as $changelog) {
            $todos[] = [
                'id' => 'read_changelog_' . $changelog->id,
                'type' => 'Read Update',
                'description' => 'Read the update from ' . $changelog->release_date->format('F j, Y'),
                'changelog_id' => $changelog->id,
            ];
        }

        // 3. Posts not liked
        $likedPostIds = $user->likes()->where('likeable_type', Post::class)->pluck('likeable_id');
        
        $postsToLike = Post::with('user:id,username')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('id', $likedPostIds)
            ->whereIn('group_id', $userGroupIds)
            ->latest()
            ->get()
            ->map(function ($post) {
                return [
                    'id' => 'like_post_' . $post->id,
                    'type' => $post->is_blog_post ? 'Like a Blog Post' : 'Like a Post',
                    'description' => "Like " . $post->user->username . "'s post.",
                    'post_id' => $post->id,
                    'group_id' => $post->group_id,
                    'content' => $post->content,
                ];
            });

        $todos = array_merge($todos, $postsToLike->all());
        Log::info("Dashboard: Final to-do list for user ID: {$user->id}", $todos);

        return Inertia::render('Dashboard', [
            'groups' => $groups,
            'notifications' => $notifications,
            'notificationsLastCheckedAt' => $user->notifications_last_checked_at,
            'todos' => $todos,
        ]);
    }
}
