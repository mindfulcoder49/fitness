<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesVictor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VictorController extends Controller
{
    public function index()
    {
        $victors = VictoryGamesVictor::withCount('entries')
            ->orderBy('display_name')
            ->get()
            ->map(fn ($v) => [
                'id'             => $v->id,
                'slug'           => $v->slug,
                'display_name'   => $v->display_name,
                'bio'            => $v->bio,
                'avatar_url'     => $v->avatar_url,
                'github_url'     => $v->github_url,
                'entries_count'  => $v->entries_count,
                'best_placement' => $v->entries()->whereNotNull('placement')->min('placement'),
            ]);

        return Inertia::render('VictoryGames/Victors', [
            'victors' => $victors,
        ]);
    }

    public function show(VictoryGamesVictor $victor)
    {
        $entries = $victor->entries()
            ->with('competition')
            ->orderByRaw('CASE WHEN placement IS NULL THEN 4 ELSE placement END')
            ->get()
            ->map(fn ($entry) => [
                'id'              => $entry->id,
                'app_url'         => $entry->app_url,
                'app_hostname'    => $entry->appHostname(),
                'app_goal'        => $entry->app_goal,
                'placement'       => $entry->placement,
                'placement_label' => $entry->placementLabel(),
                'submission_note' => $entry->submission_note,
                'entry_profile'   => $entry->entry_profile,
                'step_count'      => $entry->steps()->count(),
                'competition'     => [
                    'id'      => $entry->competition->id,
                    'slug'    => $entry->competition->slug,
                    'name'    => $entry->competition->name,
                    'held_at' => $entry->competition->held_at?->toISOString(),
                ],
            ]);

        $canEdit = auth()->check() && ($victor->isOwnedBy(auth()->user()) || auth()->user()->is_admin);

        return Inertia::render('VictoryGames/Victor', [
            'victor' => [
                'id'          => $victor->id,
                'slug'        => $victor->slug,
                'display_name'=> $victor->display_name,
                'email'       => $canEdit ? $victor->email : null,
                'bio'         => $victor->bio,
                'avatar_url'  => $victor->avatar_url,
                'github_url'  => $victor->github_url,
                'website_url' => $victor->website_url,
                'twitter_url' => $victor->twitter_url,
            ],
            'entries' => $entries,
            'canEdit' => $canEdit,
        ]);
    }

    public function update(Request $request, VictoryGamesVictor $victor)
    {
        abort_unless($victor->isOwnedBy(auth()->user()) || auth()->user()->is_admin, 403);

        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'email'        => 'nullable|email|max:255',
            'bio'          => 'nullable|string|max:2000',
            'github_url'   => 'nullable|url|max:255',
            'website_url'  => 'nullable|url|max:255',
            'twitter_url'  => 'nullable|url|max:255',
            'avatar'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($victor->avatar_path) {
                Storage::delete($victor->avatar_path);
            }
            $data['avatar_path'] = $request->file('avatar')->store('victory-games/avatars', 'public');
        }

        unset($data['avatar']);
        $victor->update($data);

        return redirect()->route('victory-games.victors.show', $victor->slug)
            ->with('success', 'Profile updated.');
    }

    /**
     * Claim an unclaimed victor profile by matching external_user_id.
     * The user submits their AIUXTester user ID; if it matches an unclaimed profile, it gets linked.
     */
    public function claim(Request $request, VictoryGamesVictor $victor)
    {
        abort_unless(auth()->check(), 401);
        abort_if($victor->user_id !== null, 409, 'This profile is already claimed.');

        $data = $request->validate([
            'external_user_id' => 'required|string|max:255',
        ]);

        abort_unless(
            $victor->external_user_id === $data['external_user_id'],
            403,
            'The provided ID does not match this profile.'
        );

        $victor->update(['user_id' => auth()->id()]);

        return redirect()->route('victory-games.victors.show', $victor->slug)
            ->with('success', 'Profile claimed! You can now edit your profile.');
    }
}
