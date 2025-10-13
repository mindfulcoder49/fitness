<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\Group;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required_without_all:image,video|nullable|string|max:50000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:10000',
            'video' => 'nullable|mimetypes:video/mp4,video/webm,video/ogg|max:20480',
            'group_id' => 'required|exists:groups,id',
            'is_for_task' => 'nullable|boolean',
        ]);

        $user = Auth::user();
        $group = $user->groups()->findOrFail($validated['group_id']);

        // Check if user is a member of the group
        if (!$group) {
            abort(403, 'You are not a member of this group.');
        }

        $content = $validated['content'];
        $isBlogPost = $request->boolean('is_blog_post');

        $currentTask = $group->tasks()->where('is_current', true)->first();
        $groupTaskId = null;
        if ($currentTask && !empty($validated['is_for_task'])) {
            // Check if user has already posted for this task today
            $hasPostedForTaskToday = $user->posts()
                ->where('group_task_id', $currentTask->id)
                ->whereDate('created_at', today())
                ->exists();

            if ($hasPostedForTaskToday) {
                return back()->withErrors(['daily_limit' => 'You have already posted for today\'s task.']);
            }
            $groupTaskId = $currentTask->id;
        }

        if ($group->pivot->role === 'prospective') {
            $hasPostedToday = $user->posts()->where('group_id', $group->id)->whereDate('created_at', today())->exists();
            if ($hasPostedToday) {
                throw ValidationException::withMessages([
                    'daily_limit' => 'You have already posted your update for today in this group.',
                ]);
            }
        }
        //log the content for debugging
        Log::info('Post content: ' . $content);

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
            'group_id' => $group->id,
            'content' => $content,
            'image_path' => $imagePath,
            'video_path' => $videoPath,
            'is_blog_post' => $isBlogPost,
            'group_task_id' => $groupTaskId,
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $request->validate([
            'content' => 'required|string|max:50000',
        ]);

        $post->update([
            'content' => $request->input('content'),
        ]);

        return redirect()->back();
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return redirect()->back();
    }
}
