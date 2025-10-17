<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Changelog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        $weekAgo = now()->subWeek();

        // User's groups
        $groups = $user->groups()->with('creator')->get();
        $userGroupIds = $groups->pluck('id');

        // Notifications are now fetched client-side

        // To-Do list is now fetched client-side

        return Inertia::render('Dashboard', [
            'groups' => $groups,
            // 'notifications' and 'notificationsLastCheckedAt' props are removed
        ]);
    }
}
