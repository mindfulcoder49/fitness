<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead()
    {
        $user = Auth::user();
        $user->notifications_last_checked_at = now();
        $user->save();

        return back();
    }
}
