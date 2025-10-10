<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class UserController extends Controller
{
    public function show(User $user)
    {
        $user->loadCount(['posts', 'comments', 'likes', 'readChangelogs']);

        // Fetch activities
        $posts = $user->posts()->where('is_blog_post', false)->get()->map(function ($post) {
            $post->activity_type = 'post';
            return $post;
        });

        $comments = $user->comments()->with('post.user')->get()->map(function ($comment) {
            $comment->activity_type = 'comment';
            return $comment;
        });

        $likes = $user->likes()->with('likeable.user')->latest()->get()->map(function ($like) {
            $like->activity_type = 'like';
            return $like;
        });

        // Merge and sort activities
        $activityFeed = $posts->concat($comments)->concat($likes)
            ->sortByDesc('created_at')
            ->values()
            ->take(50); // Limit feed size

        return Inertia::render('Users/Show', [
            'profileUser' => $user,
            'activityFeed' => $activityFeed,
        ]);
    }
}
