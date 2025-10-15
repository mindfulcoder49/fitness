<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Post;
use App\Models\Comment;
use App\Models\Like;
use App\Models\GroupTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GroupController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $groups = Group::where('is_public', true)
            ->with('creator')
            ->withCount('users')
            ->get()
            ->map(function ($group) use ($user) {
                $group->is_member = $user->groups()->where('group_id', $group->id)->exists();
                return $group;
            });

        return Inertia::render('Group/Index', [
            'groups' => $groups,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'required|boolean',
        ]);

        $user = Auth::user();

        $group = Group::create([
            'creator_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'is_public' => $user->is_admin ? $validated['is_public'] : false,
        ]);

        // Add creator as admin member
        $group->users()->attach($user->id, ['role' => 'admin']);

        return redirect()->route('groups.show', $group);
    }

    public function join(Group $group)
    {
        if (!$group->is_public) {
            abort(403, 'This group is not public.');
        }

        $group->users()->syncWithoutDetaching([Auth::id() => ['role' => 'member']]);

        return redirect()->route('groups.show', $group);
    }

    public function show(Request $request, Group $group)
    {
        Gate::authorize('view', $group);

        $user = Auth::user();
        $weekAgo = now()->subWeek();

        // Ensure user is a member of the group to view it, unless it's public
        $isMember = $user->groups()->where('group_id', $group->id)->exists();
        if (!$group->is_public && !$isMember) {
            abort(403);
        }

        $group->load('creator');
        $membership = $user->groups()->where('group_id', $group->id)->first();
        $isGroupAdmin = $membership?->pivot->role === 'admin' || $group->creator_id === $user->id;

        // New chat message count
        $newChatMessageCount = 0;
        if ($membership) {
            $lastChecked = $membership->pivot->group_messages_last_checked_at;
            $newChatMessageCount = $group->messages()
                ->where('user_id', '!=', $user->id)
                ->when($lastChecked, function ($query) use ($lastChecked) {
                    return $query->where('created_at', '>', $lastChecked);
                })
                ->count();
        }

        // Group-specific posts
        $basePostQuery = $group->posts()->with(['user', 'comments.user', 'comments.likes', 'likes', 'groupTask'])
            ->withCount('likes', 'comments');

        $posts = (clone $basePostQuery)->where('is_blog_post', false)->latest()->get();

        $processPost = function ($post) use ($user) {
            if (!$post || !$post->user) return null;
            $post->is_liked = $post->likes->contains('user_id', $user->id);
            $post->can = [
                'delete' => $user->can('delete', $post),
                'update' => $user->can('update', $post),
            ];
            $post->comments = $post->comments->filter(fn ($c) => $c->user)->map(function ($comment) use ($user) {
                $comment->is_liked = $comment->likes->contains('user_id', $user->id);
                $comment->can = ['delete' => $user->can('delete', $comment)];
                return $comment;
            })->values();
            return $post;
        };
        $posts = $posts->map($processPost)->filter()->values();

        // Handle featured post
        $featuredPost = null;
        if ($request->has('post')) {
            $featuredPost = (clone $basePostQuery)->find($request->post);
            if ($featuredPost) {
                $featuredPost = $processPost($featuredPost);
            }
        }

        // Group-specific user metrics
        $groupUserMetrics = [
            'days_posted' => $user->posts()->where('group_id', $group->id)->select(DB::raw('DATE(created_at)'))->distinct()->count(),
            'likes_on_posts' => $user->posts()->where('group_id', $group->id)->withCount('likes')->get()->sum('likes_count'),
            'likes_given' => $user->likes()->whereHasMorph(
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
            )->count(),
            'total_comments' => $user->comments()->whereHas('post', fn($q) => $q->where('group_id', $group->id))->count(),
        ];

        // Group-specific leaderboard
        $leaderboard = $group->users()->get()->map(function ($u) use ($group) {
            // Distinct post days in this group (non-blog posts)
            $distinctPostDays = $u->posts()
                ->where('group_id', $group->id)
                ->where('is_blog_post', false)
                ->select(DB::raw('DATE(created_at) as post_date'))
                ->distinct()
                ->count();

            // Comments in this group (on non-blog posts)
            $commentsCount = $u->comments()->whereHas('post', fn($q) => $q->where('group_id', $group->id)->where('is_blog_post', false))->count();

            // Likes given in this group (on non-blog posts/comments)
            $likesGivenCount = $u->likes()->whereHasMorph(
                'likeable',
                [Post::class, Comment::class],
                function ($query, $type) use ($group) {
                    if ($type === Post::class) {
                        $query->where('group_id', $group->id)->where('is_blog_post', false);
                    } elseif ($type === Comment::class) {
                        $query->whereHas('post', function ($subQuery) use ($group) {
                            $subQuery->where('group_id', $group->id)->where('is_blog_post', false);
                        });
                    }
                }
            )->count();

            // Likes received on posts in this group (non-blog posts)
            $likesOnPosts = $u->posts()->where('group_id', $group->id)->where('is_blog_post', false)->withCount('likes')->get()->sum('likes_count');

            // Likes received on comments in this group (on non-blog posts)
            $likesOnComments = $u->comments()->whereHas('post', fn($q) => $q->where('group_id', $group->id)->where('is_blog_post', false))->withCount('likes')->get()->sum('likes_count');

            // Scoring: distinct post days=3, comments=1, likes given=0.5, likes received=1
            $score = ($distinctPostDays * 3) +
                     ($commentsCount * 1) +
                     ($likesGivenCount * 0.5) +
                     ($likesOnPosts * 1) +
                     ($likesOnComments * 1);

            return [
                'id' => $u->id,
                'name' => $u->username,
                'score' => round($score),
            ];
        })->sortByDesc('score')->values()->take(20);

        // Group-specific tasks (To-Do List)
        Log::info("GroupShow: Fetching current tasks for group ID: {$group->id}");
        $currentTasks = $group->tasks()->where('is_current', true)->get();
        Log::info("GroupShow: Found {$currentTasks->count()} current tasks.", $currentTasks->pluck('id', 'title')->all());
        $todos = [];
        if ($currentTasks->isNotEmpty()) {
            $postedTaskIdsToday = $user->posts()
                ->where('group_id', $group->id)
                ->whereNotNull('group_task_id')
                ->where('created_at', '>=', now('America/New_York')->startOfDay())
                ->pluck('group_task_id');

            foreach ($currentTasks as $task) {
                if (!$postedTaskIdsToday->contains($task->id)) {
                    $todos[] = [
                        'id' => 'post_today_task_' . $task->id,
                        'type' => 'Daily Task',
                        'description' => $task->title,
                    ];
                }
            }
        }

        // Check if prospective member has posted today for the UI lock
        $hasPostedToday = false;
        if ($membership?->pivot->role === 'prospective') {
            $hasPostedToday = $user->posts()
                ->where('created_at', '>=', now('America/New_York')->startOfDay())
                ->exists();
        }

        // Group-specific notifications
        $postsForNotifications = $group->posts()
            ->with('user:id,username')
            ->where('created_at', '>=', $weekAgo)
            ->where('user_id', '!=', $user->id)
            ->get();

        $userPostIdsInGroup = $user->posts()->where('group_id', $group->id)->pluck('id');
        $commentsOnUserPosts = Comment::with('user:id,username', 'post:id,content,group_id')
            ->whereIn('post_id', $userPostIdsInGroup)
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>=', $weekAgo)
            ->get();

        $userCommentIdsInGroup = $user->comments()->whereHas('post', fn($q) => $q->where('group_id', $group->id))->pluck('id');
        $likesOnUserContent = Like::with([
            'user:id,username',
            'likeable' => function ($morphTo) {
                $morphTo->morphWith([
                    \App\Models\Comment::class => ['post:id,group_id'],
                ]);
            }
        ])
            ->where(function ($query) use ($userPostIdsInGroup, $userCommentIdsInGroup) {
                $query->where(function ($q) use ($userPostIdsInGroup) {
                    $q->where('likeable_type', Post::class)->whereIn('likeable_id', $userPostIdsInGroup);
                })->orWhere(function ($q) use ($userCommentIdsInGroup) {
                    $q->where('likeable_type', Comment::class)->whereIn('likeable_id', $userCommentIdsInGroup);
                });
            })
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>=', $weekAgo)
            ->get();

        $notifications = [
            'posts' => $postsForNotifications,
            'commentsOnUserPosts' => $commentsOnUserPosts,
            'likesOnUserContent' => $likesOnUserContent,
            'changelogs' => [], // No app-level changelogs on group page
        ];

        $viewData = [
            'group' => $group,
            'membership' => $membership,
            'isGroupAdmin' => $isGroupAdmin,
            'posts' => $posts,
            'featuredPost' => $featuredPost,
            'leaderboard' => $leaderboard,
            'currentTasks' => $currentTasks,
            'userMetrics' => $groupUserMetrics,
            'todos' => $todos,
            'hasPostedToday' => $hasPostedToday,
            'notifications' => $notifications,
            'notificationsLastCheckedAt' => $user->notifications_last_checked_at,
            'newChatMessageCount' => $newChatMessageCount,
        ];

        Log::info("GroupShow: Data being passed to view for group ID: {$group->id}", ['currentTasks_count' => $currentTasks->count()]);

        return Inertia::render('Group/Show', $viewData);
    }

    public function blog(Request $request, Group $group)
    {
        $user = Auth::user();
        $postsQuery = $group->posts()
            ->where('is_blog_post', true)
            ->with(['user', 'group', 'comments.user', 'comments.likes', 'likes'])
            ->withCount('likes', 'comments');

        $posts = (clone $postsQuery)->latest()->get();

        $processPost = function ($post) use ($user) {
            if (!$post || !$post->user) return null;
            $post->is_liked = $post->likes->contains('user_id', $user->id);
            $post->can = [
                'delete' => $user->can('delete', $post),
                'update' => $user->can('update', $post),
            ];
            $post->comments = $post->comments->filter(fn ($c) => $c->user)->map(function ($comment) use ($user) {
                $comment->is_liked = $comment->likes->contains('user_id', $user->id);
                $comment->can = ['delete' => $user->can('delete', $comment)];
                return $comment;
            })->values();
            return $post;
        };

        $processedPosts = $posts->map($processPost)->filter()->values();

        // Handle featured post
        $featuredPost = null;
        if ($request->has('post')) {
            $featuredPost = (clone $postsQuery)->find($request->post);
            if ($featuredPost) {
                $featuredPost = $processPost($featuredPost);
            }
        }

        return Inertia::render('Blog/Index', [
            'posts' => $processedPosts,
            'featuredPost' => $featuredPost,
            'group' => $group,
        ]);
    }

    public function admin(Group $group)
    {
        Gate::authorize('manageMembers', $group);

        $group->load([
            'users',
            'creator',
            'tasks',
            'posts' => fn($q) => $q->with('user')->latest(),
            'posts.comments' => fn($q) => $q->with('user')->latest(),
            'posts.likes' => fn($q) => $q->with('user')->latest(),
            'posts.comments.likes' => fn($q) => $q->with('user')->latest(),
        ]);

        return Inertia::render('Group/Admin', [
            'group' => $group,
        ]);
    }

    public function setCurrentTask(GroupTask $task)
    {
        Gate::authorize('manageTasks', $task->group);

        // Only update the specific task's is_current field
        $task->update(['is_current' => true]);

        return back();
    }

    public function unsetCurrentTask(GroupTask $task)
    {
        Gate::authorize('manageTasks', $task->group);

        $task->update(['is_current' => false]);

        return back();
    }
}
