<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Storage;
use App\Models\GroupTask;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use App\Mail\ApplicationInvitation;
use App\Mail\GroupLaunched;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        $groupsEnabled = filter_var(SiteSetting::get('groups_enabled', '1'), FILTER_VALIDATE_BOOLEAN);

        return Inertia::render('Admin/Dashboard', [
            'users' => User::all(),
            'groups' => $groupsEnabled ? Group::with([
                'users',
                'creator',
                'tasks',
                'posts' => fn($q) => $q->with(['user', 'groupTask'])->latest(),
                'posts.comments' => fn($q) => $q->with(['user', 'likes.user'])->latest(),
                'posts.likes' => fn($q) => $q->with('user')->latest(),
                'posts.comments.likes' => fn($q) => $q->with('user')->latest(),
            ])->get() : [],
            'groupsEnabled' => $groupsEnabled,
        ]);
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email'    => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', Rules\Password::defaults()],
        ]);

        User::create([
            'name'     => $request->name,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'User created.');
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|max:2048',
        ]);

        $existing = SiteSetting::get('site_logo_path');
        if ($existing) {
            Storage::disk('public')->delete($existing);
        }

        $path = $request->file('logo')->store('site', 'public');
        SiteSetting::set('site_logo_path', $path);

        return back()->with('success', 'Logo updated.');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'groups_enabled' => 'required|boolean',
        ]);

        SiteSetting::set('groups_enabled', $request->boolean('groups_enabled') ? '1' : '0');

        return back()->with('success', 'Settings updated.');
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
            GroupTask::where('group_id', $task->group_id)->update(['is_current' => false]);
            $task->update(['is_current' => true]);
        });

        return back();
    }

    public function unsetCurrentTask(GroupTask $task)
    {
        $task->update(['is_current' => false]);

        return back();
    }

    public function updateGroupMinMembers(Request $request, Group $group)
    {
        $request->validate([
            'min_members' => ['nullable', 'integer', 'min:1'],
        ]);

        $group->update(['min_members' => $request->min_members]);

        return back();
    }

    public function destroyGroup(Group $group)
    {
        $group->delete();

        return redirect()->route('dashboard')->with('success', 'Group has been deleted.');
    }

    public function launchGroup(Group $group)
    {
        if ($group->launched_at) {
            return back()->with('error', 'Group is already launched.');
        }

        $group->update(['launched_at' => now()]);

        return back()->with('success', 'Group has been launched.');
    }

    public function unlaunchGroup(Group $group)
    {
        $group->update(['launched_at' => null]);

        return back()->with('success', 'Group has been unlaunched.');
    }

    public function sendGroupEmail(Group $group)
    {
        Log::info('sendGroupEmail: Starting for group', [
            'group_id' => $group->id,
            'group_name' => $group->name,
        ]);

        $group->load('users');

        Log::info('sendGroupEmail: Loaded members', [
            'member_count' => $group->users->count(),
            'member_emails' => $group->users->pluck('email')->toArray(),
        ]);

        if (!$group->launched_at) {
            $group->update(['launched_at' => now()]);
            Log::info('sendGroupEmail: Set launched_at for group', ['group_id' => $group->id]);
        }

        $sent = 0;
        $failed = 0;

        foreach ($group->users as $member) {
            try {
                Log::info('sendGroupEmail: Sending email to member', [
                    'member_id' => $member->id,
                    'member_email' => $member->email,
                ]);

                Mail::to($member->email)->send(new GroupLaunched($group));

                $sent++;
                Log::info('sendGroupEmail: Email sent successfully', [
                    'member_email' => $member->email,
                ]);
            } catch (\Exception $e) {
                $failed++;
                Log::error('sendGroupEmail: Failed to send email', [
                    'member_email' => $member->email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        Log::info('sendGroupEmail: Completed', [
            'group_id' => $group->id,
            'sent' => $sent,
            'failed' => $failed,
        ]);

        if ($failed > 0) {
            return back()->with('error', "Launch email sent to {$sent} members, but {$failed} failed. Check logs for details.");
        }

        return back()->with('success', "Launch email sent to all {$sent} group members.");
    }
}
