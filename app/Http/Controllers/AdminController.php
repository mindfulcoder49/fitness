<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationInvitation;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'users' => User::withCount(['posts', 'likes', 'comments'])->get(),
            'posts' => Post::with('user')->withCount(['likes', 'comments'])->latest()->get(),
            'likes' => Like::with('user', 'likeable')->latest()->get(),
            'comments' => Comment::with('user', 'post')->latest()->get(),
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate(['role' => 'required|in:prospective,initiate,full_member,admin']);
        $user->update(['role' => $request->role]);
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

    public function updateUserFitnessGoal(Request $request, User $user)
    {
        $request->validate(['daily_fitness_goal' => 'nullable|string|max:255']);
        $user->update(['daily_fitness_goal' => $request->daily_fitness_goal]);
        return back();
    }

    public function sendInvitation(User $user)
    {
        if (!$user->invitation_sent_at) {
            // Logic to send invitation email
            // Mail::to($user->email)->send(new ApplicationInvitation($user));
            $user->forceFill(['invitation_sent_at' => now()])->save();
        }
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
}
