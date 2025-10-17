<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        $weekAgo = now()->subWeek();
        $group = $request->has('group_id') ? Group::find($request->group_id) : null;

        $userGroupIds = $group ? collect([$group->id]) : $user->groups()->pluck('groups.id');

        // Posts
        $posts = Post::with('user:id,username', 'group:id,name')
            ->whereIn('group_id', $userGroupIds)
            ->where('created_at', '>=', $weekAgo)
            ->where('user_id', '!=', $user->id)
            ->get();

        // Comments on user's posts
        $userPostIds = $user->posts()->whereIn('group_id', $userGroupIds)->pluck('id');
        $comments = Comment::with('user:id,username', 'post:id,content,group_id', 'post.group:id,name')
            ->whereIn('post_id', $userPostIds)
            ->where('user_id', '!=', $user->id)
            ->where('created_at', '>=', $weekAgo)
            ->get();

        // Likes on user's content
        $userCommentIds = $user->comments()->whereHas('post', fn($q) => $q->whereIn('group_id', $userGroupIds))->pluck('id');
        $likes = Like::with([
            'user:id,username',
            'likeable' => function ($morphTo) {
                $morphTo->morphWith([
                    Post::class => ['group:id,name'],
                    Comment::class => ['post:id,group_id', 'post.group:id,name'],
                ]);
            }
        ])
        ->where(function ($query) use ($userPostIds, $userCommentIds) {
            $query->where(fn($q) => $q->where('likeable_type', Post::class)->whereIn('likeable_id', $userPostIds))
                  ->orWhere(fn($q) => $q->where('likeable_type', Comment::class)->whereIn('likeable_id', $userCommentIds));
        })
        ->where('user_id', '!=', $user->id)
        ->where('created_at', '>=', $weekAgo)
        ->get();

        // Changelogs (only for dashboard)
        $changelogs = collect();
        if (!$group) {
            $changelogs = Changelog::whereDoesntHave('readByUsers', fn($q) => $q->where('user_id', $user->id))->get();
        }

        return response()->json([
            'posts' => $posts,
            'commentsOnUserPosts' => $comments,
            'likesOnUserContent' => $likes,
            'changelogs' => $changelogs,
            'lastChecked' => $user->notifications_last_checked_at,
        ]);
    }

    public function markAsRead()
    {
        $user = Auth::user();
        $user->notifications_last_checked_at = now();
        $user->save();

        return back();
    }
}
