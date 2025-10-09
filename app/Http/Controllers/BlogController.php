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

        $posts = Post::where('is_blog_post', true)
            ->with(['user', 'comments.user', 'comments.likes', 'likes'])
            ->withCount('likes', 'comments')
            ->latest()
            ->get()
            ->map(function ($post) use ($user) {
                if (!$post->user) {
                    return null;
                }

                $post->is_liked = $post->likes->contains('user_id', $user->id);
                $post->can = ['delete' => $user->can('delete', $post)];

                $post->comments = $post->comments->filter(function ($comment) {
                    return $comment->user;
                })->map(function ($comment) use ($user) {
                    $comment->is_liked = $comment->likes->contains('user_id', $user->id);
                    $comment->can = ['delete' => $user->can('delete', $comment)];
                    return $comment;
                })->values();

                return $post;
            })
            ->filter();

        return Inertia::render('Blog/Index', [
            'posts' => $posts->values(),
        ]);
    }
}
