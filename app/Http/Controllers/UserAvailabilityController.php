<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\UserAvailability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class UserAvailabilityController extends Controller
{
    public function index(Group $group)
    {
        $user = Auth::user();
        
        return Inertia::render('Availability/Index', [
            'group' => $group,
            'isGroupAdmin' => false, // This is now only for user's own availability
        ]);
    }

    public function getData(Request $request, Group $group)
    {
        $user = Auth::user();

        $userAvailability = UserAvailability::firstOrCreate(
            ['user_id' => $user->id, 'group_id' => $group->id],
            ['availability' => []]
        )->availability ?? [];

        return response()->json([
            'userAvailability' => $userAvailability,
            'adminData' => null, // Admin data is now loaded in GroupController@admin
        ]);
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'availability' => 'required|array',
            'availability.*' => 'date_format:Y-m-d',
        ]);

        $user = Auth::user();

        UserAvailability::updateOrCreate(
            ['user_id' => $user->id, 'group_id' => $group->id],
            ['availability' => $request->availability]
        );

        return response()->json(['message' => 'Availability updated successfully.']);
    }
}
