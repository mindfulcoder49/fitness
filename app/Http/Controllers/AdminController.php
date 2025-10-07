<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $likes = Like::with('user', 'likeable')->latest()->get()->filter(function ($like) {
            return $like->user && $like->likeable;
        });

        return Inertia::render('Admin/Dashboard', [
            'users' => User::withCount(['posts', 'likes', 'comments'])
                ->orderBy('created_at', 'desc')
                ->get(),
            'posts' => Post::with('user')->withCount('likes', 'comments')->latest()->get(),
            'likes' => $likes->values(), // ->values() re-indexes the collection after filtering
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::in(['prospective', 'initiate', 'full_member', 'admin'])],
        ]);

        $user->update(['role' => $request->role]);

        return back();
    }

    public function updateUserMediaPermissions(Request $request, User $user)
    {
        $request->validate([
            'can_post_images' => 'required|boolean',
            'can_post_videos' => 'required|boolean',
        ]);

        $user->update($request->only(['can_post_images', 'can_post_videos']));

        return back();
    }

    public function sendInvitation(User $user)
    {
        $user->update(['invitation_sent_at' => now()]);

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

    public function destroyLike(Like $like)
    {
        $like->delete();
        return back();
    }
}
