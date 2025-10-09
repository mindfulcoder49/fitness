<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request)
    {
        $user = Auth::user();
        $content = $request->input('content');
        $isBlogPost = $request->boolean('is_blog_post');

        if ($user->role === 'prospective') {
            $hasPostedToday = $user->posts()->whereDate('created_at', today())->exists();
            if ($hasPostedToday) {
                throw ValidationException::withMessages([
                    'daily_limit' => 'You have already posted your update for today.',
                ]);
            }
            if ($user->daily_fitness_goal) {
                $content = "I completed my daily fitness goal: " . $user->daily_fitness_goal . " and... " . $request->input('content');
            }
        }

        $request->validate([
            'content' => 'required_without_all:image,video|nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'video' => 'nullable|mimetypes:video/mp4,video/webm,video/ogg|max:20480',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            if (!$user->can_post_images) {
                return back()->withErrors(['image' => 'You do not have permission to post images.']);
            }
            $imagePath = $request->file('image')->store('posts/images', 'public');
        }

        $videoPath = null;
        if ($request->hasFile('video')) {
            if (!$user->can_post_videos) {
                return back()->withErrors(['video' => 'You do not have permission to post videos.']);
            }
            $videoPath = $request->file('video')->store('posts/videos', 'public');
        }

        $user->posts()->create([
            'content' => $content,
            'image_path' => $imagePath,
            'video_path' => $videoPath,
            'is_blog_post' => $isBlogPost,
        ]);

        return redirect()->route('dashboard');
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->route('dashboard');
    }
}
