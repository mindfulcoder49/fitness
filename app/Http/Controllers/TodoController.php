<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use App\Models\Group;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TodoController extends Controller
{
    public function getTodos(Request $request)
    {
        $user = Auth::user();
        $group = $request->has('group_id') ? Group::find($request->group_id) : null;

        $todos = [];

        // Determine which group IDs to use
        $userGroupIds = $group ? collect([$group->id]) : $user->groups()->pluck('groups.id');

        // 1. Group-specific tasks (if in a group context)
        if ($group) {
            $currentTasks = $group->tasks()->where('is_current', true)->get();
            if ($currentTasks->isNotEmpty()) {
                $postedTaskIdsToday = $user->posts()
                    ->where('group_id', $group->id)
                    ->whereNotNull('group_task_id')
                    ->where('created_at', '>=', now('America/New_York')->startOfDay())
                    ->pluck('group_task_id');

                foreach ($currentTasks as $task) {
                    if (!$postedTaskIdsToday->contains($task->id)) {
                        $todos[] = [
                            'id' => 'task_' . $task->id,
                            'type' => 'Daily Task',
                            'description' => $task->title,
                            'details' => "Post for the task in {$group->name}.",
                            'link' => route('groups.show', $group),
                            'action_type' => 'link',
                        ];
                    }
                }
            }
        }

        // 2. Unread changelogs (only on dashboard)
        if (!$group) {
            $unreadChangelogs = Changelog::whereDoesntHave('readByUsers', fn($q) => $q->where('user_id', $user->id))->get();
            foreach ($unreadChangelogs as $changelog) {
                $todos[] = [
                    'id' => 'changelog_' . $changelog->id,
                    'type' => 'Read Update',
                    'description' => 'Read the update from ' . $changelog->release_date->format('F jS'),
                    'details' => 'A new site update is available.',
                    'link' => route('changelog.index'),
                    'action_type' => 'link',
                    'action_meta' => ['method' => 'post', 'route' => route('changelog.read', $changelog->id)],
                ];
            }
        }

        // 3. Unliked posts
        $likedPostIds = $user->likes()->where('likeable_type', Post::class)->pluck('likeable_id');
        $postsToLike = Post::with('user:id,username', 'group:id,name')
            ->where('user_id', '!=', $user->id)
            ->whereNotIn('id', $likedPostIds)
            ->whereIn('group_id', $userGroupIds)
            ->latest()
            ->take(10) // Limit to avoid overwhelming the user
            ->get();

        foreach ($postsToLike as $post) {
            $isBlog = $post->is_blog_post;
            $description = "Like " . $post->user->username . "'s " . ($isBlog ? 'blog post' : 'post');
            if (!$group) { // Only add group name on dashboard
                $description .= " in " . $post->group->name;
            }

            $todos[] = [
                'id' => 'like_post_' . $post->id,
                'type' => $isBlog ? 'Like a Blog Post' : 'Like a Post',
                'description' => $description,
                'details' => '"' . \Illuminate\Support\Str::limit($post->content, 100) . '"',
                'link' => $isBlog ? route('groups.blog', ['group' => $post->group_id, 'post' => $post->id]) : route('groups.show', ['group' => $post->group_id, 'post' => $post->id]),
                'action_type' => 'link',
                'action_meta' => [
                    'method' => 'post',
                    'route' => route('likes.store'),
                    'data' => [
                        'likeable_id' => $post->id,
                        'likeable_type' => Post::class,
                    ],
                ],
            ];
        }

        return response()->json($todos);
    }
}
