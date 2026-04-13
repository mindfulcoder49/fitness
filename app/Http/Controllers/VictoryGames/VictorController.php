<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesVictor;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VictorController extends Controller
{
    public function index()
    {
        $authVictor = auth()->user()?->victoryGamesVictor;

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
            'authVictor' => $authVictor ? [
                'id' => $authVictor->id,
                'slug' => $authVictor->slug,
                'display_name' => $authVictor->display_name,
            ] : null,
        ]);
    }

    public function show(Request $request, VictoryGamesVictor $victor)
    {
        $authUser = $request->user();
        $canEdit = $authUser && ($victor->isOwnedBy($authUser) || $authUser->is_admin);

        // Competition entries (have a competition_id)
        $competitionEntries = $victor->entries()
            ->whereNotNull('competition_id')
            ->with(['competition', 'app'])
            ->orderByRaw('CASE WHEN placement IS NULL THEN 4 ELSE placement END')
            ->get()
            ->map(fn ($e) => $this->serializeEntry($e, $authUser));

        // Unassigned standalone runs (no competition, no app yet)
        $unassignedRuns = $canEdit
            ? $victor->entries()
                ->whereNull('competition_id')
                ->whereNull('app_id')
                ->orderByDesc('submitted_at')
                ->get()
                ->map(fn ($e) => $this->serializeEntry($e, $authUser))
            : collect();

        // Apps this victor belongs to
        $apps = $victor->apps()
            ->withCount('entries')
            ->orderByPivot('created_at')
            ->get()
            ->map(fn ($app) => [
                'id'            => $app->id,
                'slug'          => $app->slug,
                'name'          => $app->name,
                'description'   => $app->description,
                'current_url'   => $app->current_url,
                'entries_count' => $app->entries_count,
                'role'          => $app->pivot->role,
            ]);

        // All apps for the assign-run dropdown (only when canEdit)
        $allApps = $canEdit
            ? $victor->apps()->get()->map(fn ($a) => ['id' => $a->id, 'name' => $a->name])
            : collect();

        $isAdmin = $authUser?->is_admin;
        $claimedUser = ($isAdmin && $victor->user_id)
            ? $victor->user()->select('id', 'name', 'username')->first()
            : null;

        return Inertia::render('VictoryGames/Victor', [
            'victor' => [
                'id'           => $victor->id,
                'slug'         => $victor->slug,
                'display_name' => $victor->display_name,
                'email'        => $canEdit ? $victor->email : null,
                'bio'          => $victor->bio,
                'avatar_url'   => $victor->avatar_url,
                'github_url'   => $victor->github_url,
                'website_url'  => $victor->website_url,
                'twitter_url'  => $victor->twitter_url,
                'is_claimed'   => $victor->user_id !== null,
                'claimed_user' => $claimedUser ? [
                    'id'       => $claimedUser->id,
                    'name'     => $claimedUser->name,
                    'username' => $claimedUser->username,
                ] : null,
            ],
            'entries'         => $competitionEntries,
            'unassignedRuns'  => $unassignedRuns,
            'apps'            => $apps,
            'allApps'         => $allApps,
            'canEdit'         => $canEdit,
        ]);
    }

    private function serializeEntry(\App\Models\VictoryGamesEntry $entry, ?User $user): array
    {
        return [
            'id'              => $entry->id,
            'app_id'          => $entry->app_id,
            'app_url'         => $entry->app_url,
            'app_hostname'    => $entry->appHostname(),
            'app_goal'        => $entry->app_goal,
            'placement'       => $entry->placement,
            'placement_label' => $entry->placementLabel(),
            'submission_note' => $entry->submission_note,
            'entry_profile'   => $entry->entry_profile,
            'step_count'      => $entry->steps()->count(),
            'submitted_at'    => $entry->submitted_at?->toISOString(),
            'session_status'  => $entry->session_status,
            'is_active_native_run' => $entry->isActiveNativeRun(),
            'can_delete'      => $entry->canBeDeletedBy($user),
            'competition'     => $entry->competition ? [
                'id'      => $entry->competition->id,
                'slug'    => $entry->competition->slug,
                'name'    => $entry->competition->name,
                'held_at' => $entry->competition->held_at?->toISOString(),
            ] : null,
            'app'             => $entry->app ? [
                'id'   => $entry->app->id,
                'slug' => $entry->app->slug,
                'name' => $entry->app->name,
            ] : null,
        ];
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

    public function store(Request $request)
    {
        abort_unless(auth()->check(), 401);

        if ($request->user()->victoryGamesVictor) {
            return redirect()
                ->route('victory-games.victors.show', $request->user()->victoryGamesVictor)
                ->with('success', 'You already have a victor profile.');
        }

        $data = $request->validate([
            'display_name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'github_url' => 'nullable|url|max:255',
            'website_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
        ]);

        $victor = VictoryGamesVictor::create([
            'user_id' => $request->user()->id,
            'email' => $request->user()->email,
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
            'slug' => VictoryGamesVictor::generateSlug($data['display_name']),
            'display_name' => $data['display_name'],
            'bio' => $data['bio'] ?? null,
            'github_url' => $data['github_url'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'twitter_url' => $data['twitter_url'] ?? null,
        ]);

        return redirect()
            ->route('victory-games.victors.show', $victor)
            ->with('success', 'Victor profile created.');
    }

    public function destroy(Request $request, VictoryGamesVictor $victor)
    {
        $user = $request->user();
        abort_unless($victor->isOwnedBy($user) || $user->is_admin, 403);

        $isOwnProfile = $victor->isOwnedBy($user);
        $displayName  = $victor->display_name;

        if ($victor->avatar_path) {
            Storage::delete($victor->avatar_path);
        }

        $victor->delete(); // certificates and app pivot rows cascade; entries get victor_id = null

        if ($isOwnProfile) {
            return redirect()->route('victory-games.home')
                ->with('success', 'Your Victor profile has been deleted.');
        }

        return redirect()->route('victory-games.victors')
            ->with('success', "Victor profile \"{$displayName}\" has been deleted.");
    }

    /**
     * Claim an unclaimed victor profile.
     * Accepts the AIUXTester email address or external user ID — whichever the user has.
     */
    public function claim(Request $request, VictoryGamesVictor $victor)
    {
        abort_unless(auth()->check(), 401);
        abort_if($victor->user_id !== null, 409, 'This profile is already claimed.');

        $data = $request->validate([
            'identity' => 'required|string|max:255',
        ]);

        $matches = ($victor->email && strtolower($victor->email) === strtolower($data['identity']))
            || ($victor->external_user_id && $victor->external_user_id === $data['identity']);

        if (! $matches) {
            throw ValidationException::withMessages([
                'identity' => 'That email or ID does not match this profile.',
            ]);
        }

        $victor->update(['user_id' => auth()->id()]);

        return redirect()->route('victory-games.victors.show', $victor->slug)
            ->with('success', 'Profile claimed! You can now edit your profile.');
    }

    /**
     * Admin-only: assign a victor profile to any user by username or email.
     * Passing user_identifier = '' unlinks the profile.
     */
    public function assignUser(Request $request, VictoryGamesVictor $victor)
    {
        abort_unless($request->user()->is_admin, 403);

        $data = $request->validate([
            'user_identifier' => 'nullable|string|max:255',
        ]);

        $identifier = trim($data['user_identifier'] ?? '');

        if ($identifier === '') {
            $victor->update(['user_id' => null]);

            return back()->with('success', 'Victor profile unlinked from user.');
        }

        $user = User::where('username', $identifier)->orWhere('email', $identifier)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'user_identifier' => 'No user found with that username or email.',
            ]);
        }

        $existing = $user->victoryGamesVictor;
        if ($existing && $existing->id !== $victor->id) {
            throw ValidationException::withMessages([
                'user_identifier' => "That user already owns the Victor profile \"{$existing->display_name}\".",
            ]);
        }

        $victor->update(['user_id' => $user->id]);

        return back()->with('success', "Victor profile assigned to {$user->name} (@{$user->username}).");
    }
}
