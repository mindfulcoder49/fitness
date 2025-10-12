<?php

namespace App\Http\Controllers;

use App\Models\Changelog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChangelogController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $changelogs = Changelog::latest('release_date')->get();
        $readChangelogIds = $user->readChangelogs()->pluck('changelog_id')->toArray();

        return Inertia::render('Changelog/Index', [
            'changelogs' => $changelogs,
            'readChangelogIds' => $readChangelogIds,
        ]);
    }

    public function markAsRead(Changelog $changelog)
    {
        $user = Auth::user();

        if (!$user->readChangelogs->contains($changelog->id)) {
            $user->readChangelogs()->attach($changelog->id);
        }

        return back();
    }
}
