<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Meetup;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MeetupController extends Controller
{
    use AuthorizesRequests;

    public function index(Group $group)
    {
        $meetups = $group->meetups()->with(['users' => function ($query) {
            $query->select('users.id', 'users.name', 'users.username');
        }])->orderBy('scheduled_at', 'asc')->get();

        return Inertia::render('Meetup/Index', [
            'group' => $group,
            'meetups' => $meetups,
        ]);
    }

    public function store(Request $request, Group $group)
    {
        Log::info('Meetup store request received for group: ' . $group->id, $request->all());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'scheduled_at' => 'required|date',
        ]);

        try {
            $group->meetups()->create($validated);
            Log::info('Meetup created successfully for group: ' . $group->id);
            return back()->with('success', 'Meetup created successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create meetup for group: ' . $group->id . ' - ' . $e->getMessage());
            return back()->with('error', 'Failed to create meetup.');
        }
    }

    public function update(Request $request, Meetup $meetup)
    {
        $this->authorize('update', $meetup);

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:255',
            'location' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'scheduled_at' => 'required|date',
        ]);

        $meetup->update($request->all());

        return back()->with('success', 'Meetup updated successfully.');
    }

    public function destroy(Meetup $meetup)
    {
        $this->authorize('destroy', $meetup);
        $meetup->delete();

        return back()->with('success', 'Meetup deleted successfully.');
    }

    public function rsvp(Request $request, Meetup $meetup)
    {
        $request->validate([
            'status' => 'required|string|in:attending,interested,not_attending',
        ]);

        $user = Auth::user();

        if ($request->status === 'not_attending') {
            $meetup->users()->detach($user->id);
        } else {
            $meetup->users()->syncWithoutDetaching([
                $user->id => ['status' => $request->status]
            ]);
        }

        return back()->with('success', 'RSVP updated successfully.');
    }
}
