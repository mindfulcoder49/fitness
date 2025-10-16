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
            'group_task_id' => 'nullable|exists:group_tasks,id',
        ]);

        $user = Auth::user();
        $group = $user->groups()->findOrFail($validated['group_id']);

        // Check if user is a member of the group
        if (!$group) {
            abort(403, 'You are not a member of this group.');
        }

        $content = $validated['content'];
        $isBlogPost = $request->boolean('is_blog_post');

        $groupTaskId = $validated['group_task_id'] ?? null;
        if ($groupTaskId) {
            $task = $group->tasks()->findOrFail($groupTaskId);
            // Check if user has already posted for this task today
            $today = today('America/New_York');
            Log::info("Checking for existing post for task_id: {$task->id} on date: " . $today->toDateString());

            $startOfDay = $today->copy()->startOfDay();
            $endOfDay = $today->copy()->endOfDay();

            Log::info("Start of day (America/New_York): " . $startOfDay->toDateTimeString());
            Log::info("End of day (America/New_York): " . $endOfDay->toDateTimeString());
            Log::info("Start of day (UTC): " . $startOfDay->copy()->utc()->toDateTimeString());
            Log::info("End of day (UTC): " . $endOfDay->copy()->utc()->toDateTimeString());

            $existingPost = $user->posts()
                ->where('group_task_id', $task->id)
                ->where('created_at', '>=', $startOfDay->copy()->utc())
                ->where('created_at', '<=', $endOfDay->copy()->utc())
                ->first();

            if ($existingPost) {
                Log::info("Existing post found for task_id: {$task->id}. Post created_at (UTC): " . $existingPost->created_at);
                Log::info("Existing post created_at converted to America/New_York: " . $existingPost->created_at->setTimezone('America/New_York'));
                Log::info("Comparison date (America/New_York): " . $today);
                return back()->withErrors(['daily_limit' => 'You have already posted for this task today.']);
            }
        }

        if ($group->pivot->role === 'prospective') {
            $today = today('America/New_York');
            Log::info("Checking for existing post for prospective user in group_id: {$group->id} on date: " . $today->toDateString());

            $startOfDay = $today->copy()->startOfDay();
            $endOfDay = $today->copy()->endOfDay();

            Log::info("Start of day (America/New_York): " . $startOfDay->toDateTimeString());
            Log::info("End of day (America/New_York): " . $endOfDay->toDateTimeString());
            Log::info("Start of day (UTC): " . $startOfDay->copy()->utc()->toDateTimeString());
            Log::info("End of day (UTC): " . $endOfDay->copy()->utc()->toDateTimeString());

            $existingPost = $user->posts()
                ->where('group_id', $group->id)
                ->where('created_at', '>=', $startOfDay->copy()->utc())
                ->where('created_at', '<=', $endOfDay->copy()->utc())
                ->first();

            if ($existingPost) {
                Log::info("Existing post found for prospective user in group_id: {$group->id}. Post created_at (UTC): " . $existingPost->created_at);
                Log::info("Existing post created_at converted to America/New_York: " . $existingPost->created_at->setTimezone('America/New_York'));
                Log::info("Comparison date (America/New_York): " . $today);
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
