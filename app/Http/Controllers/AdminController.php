<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupTask;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ApplicationInvitation;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'users' => User::all(),
            'groups' => Group::with([
                'users',
                'creator',
                'tasks',
                'posts' => fn($q) => $q->with(['user', 'groupTask'])->latest(),
                'posts.comments' => fn($q) => $q->with('user')->latest(),
                'posts.likes' => fn($q) => $q->with('user')->latest(),
                'posts.comments.likes' => fn($q) => $q->with('user')->latest(),
            ])->get(),
        ]);
    }

    public function updateGroupMemberRole(Request $request, Group $group, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::in(['member', 'admin'])],
        ]);

        // Prevent changing the group creator's role
        if ($group->creator_id === $user->id) {
            return back()->withErrors(['role' => 'Cannot change the role of the group creator.']);
        }

        $group->users()->updateExistingPivot($user->id, [
            'role' => $request->role,
        ]);

        return back();
    }

    public function updateUserMediaPermissions(Request $request, User $user)
    {
        $user->update([
            'can_post_images' => $request->boolean('can_post_images'),
            'can_post_videos' => $request->boolean('can_post_videos'),
        ]);
        return back();
    }

    public function destroyUser(User $user)
    {
        // Add a check to prevent admin from deleting themselves
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return back();
    }

    public function destroyPost(Post $post)
    {
        $post->delete();
        return back();
    }

    public function toggleBlogPost(Request $request, Post $post)
    {
        $request->validate(['is_blog_post' => 'required|boolean']);
        $post->update(['is_blog_post' => $request->is_blog_post]);
        return back();
    }

    public function toggleGroupPublicStatus(Request $request, Group $group)
    {
        $request->validate(['is_public' => 'required|boolean']);
        $group->update(['is_public' => $request->is_public]);
        return back();
    }

    public function destroyLike(Like $like)
    {
        $like->delete();
        return back();
    }

    public function destroyComment(Comment $comment)
    {
        $comment->delete();
        return back();
    }

    public function storeTask(Request $request, Group $group)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $group->tasks()->create($validated);
        return back();
    }

    public function updateTask(Request $request, GroupTask $task)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $task->update($validated);
        return back();
    }

    public function destroyTask(GroupTask $task)
    {
        $task->delete();
        return back();
    }

    public function setCurrentTask(GroupTask $task)
    {
        DB::transaction(function () use ($task) {
            $task->update(['is_current' => true]);
        });
        return back();
    }
}
