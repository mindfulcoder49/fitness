<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\GroupMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class GroupMessageController extends Controller
{
    public function show(Group $group)
    {
        Gate::authorize('view', $group);

        // Update the last checked timestamp for the user in this group
        $user = Auth::user();
        Log::info('User ' . $user->id . ' is viewing group ' . $group->id);

        $membership = $user->groups()->where('group_id', $group->id)->first();

        if ($membership) {
            Log::info('Membership pivot record found for user ' . $user->id . ' in group ' . $group->id . '. Attempting to update timestamp.');
            $user->groups()->updateExistingPivot($group->id, [
                'group_messages_last_checked_at' => now(),
            ]);
            Log::info('Timestamp update executed for user ' . $user->id . ' in group ' . $group->id . '.');
        } else {
            Log::warning('No membership pivot record found for user ' . $user->id . ' in group ' . $group->id . '. Cannot update timestamp.');
        }

        $messages = $group->messages()->latest()->limit(50)->get()->reverse()->values();

        return Inertia::render('Group/Chat', [
            'group' => $group,
            'initialMessages' => $messages,
        ]);
    }

    public function index(Request $request, Group $group)
    {
        Gate::authorize('view', $group);

        $query = $group->messages();

        if ($request->has('after_id')) {
            $query->where('id', '>', $request->input('after_id'));
        }

        $messages = $query->latest()->get();

        return response()->json($messages);
    }

    public function store(Request $request, Group $group)
    {
        Gate::authorize('view', $group);

        $validated = $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $message = $group->messages()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
        ]);

        return response()->json($message->load('user'));
    }
}
