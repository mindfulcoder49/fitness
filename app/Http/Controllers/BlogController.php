<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $posts = Post::with(['user', 'comments.user', 'comments.likes', 'likes'])
            ->withCount('likes', 'comments')
            ->where('is_blog_post', true)
            ->whereNull('group_id') // Only fetch site-wide blog posts
            ->latest()
            ->get();

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

        return Inertia::render('Blog/Index', [
            'posts' => $processedPosts,
        ]);
    }
}
