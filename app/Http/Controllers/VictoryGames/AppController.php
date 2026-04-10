<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppController extends Controller
{
    // ── Public ─────────────────────────────────────────────────────────────────

    public function show(VictoryGamesApp $app)
    {
        $app->load(['victors', 'entries.competition', 'entries.steps']);

        $authUser   = auth()->user();
        $authVictor = $authUser ? VictoryGamesVictor::where('user_id', $authUser->id)->first() : null;
        $canEdit    = $authVictor && ($app->isOwnedBy($authVictor) || ($authUser && $authUser->is_admin));

        $entries = $app->entries()
            ->with(['competition', 'steps'])
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn ($e) => $this->serializeEntry($e));

        return Inertia::render('VictoryGames/App', [
            'app' => [
                'id'          => $app->id,
                'name'        => $app->name,
                'slug'        => $app->slug,
                'description' => $app->description,
                'current_url' => $app->current_url,
                'victors'     => $app->victors->map(fn ($v) => [
                    'id'           => $v->id,
                    'slug'         => $v->slug,
                    'display_name' => $v->display_name,
                    'avatar_url'   => $v->avatar_url,
                    'role'         => $v->pivot->role,
                ]),
            ],
            'entries' => $entries,
            'canEdit' => $canEdit,
        ]);
    }

    // ── Auth-gated ─────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $victor = $this->requireVictor();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'current_url' => 'nullable|url|max:500',
        ]);

        $app = VictoryGamesApp::create([
            'name'        => $data['name'],
            'slug'        => VictoryGamesApp::generateSlug($data['name']),
            'description' => $data['description'] ?? null,
            'current_url' => $data['current_url'] ?? null,
        ]);

        $app->victors()->attach($victor->id, ['role' => 'owner']);

        return redirect()->route('victory-games.apps.show', $app->slug)
            ->with('success', 'App created.');
    }

    public function update(Request $request, VictoryGamesApp $app)
    {
        $this->requireAppEdit($app);

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'current_url' => 'nullable|url|max:500',
        ]);

        $app->update($data);

        return redirect()->route('victory-games.apps.show', $app->slug)
            ->with('success', 'App updated.');
    }

    public function addMember(Request $request, VictoryGamesApp $app)
    {
        $this->requireAppEdit($app);

        $data = $request->validate([
            'victor_slug' => 'required|string|exists:victory_games_victors,slug',
        ]);

        $member = VictoryGamesVictor::where('slug', $data['victor_slug'])->firstOrFail();

        if (!$app->isMember($member)) {
            $app->victors()->attach($member->id, ['role' => 'member']);
        }

        return back()->with('success', "{$member->display_name} added to team.");
    }

    public function removeMember(VictoryGamesApp $app, VictoryGamesVictor $victor)
    {
        $this->requireAppEdit($app);

        // Can't remove the last owner
        $ownerCount = $app->victors()->wherePivot('role', 'owner')->count();
        if ($ownerCount === 1 && $app->isOwnedBy($victor)) {
            return back()->withErrors(['member' => 'Cannot remove the only owner.']);
        }

        $app->victors()->detach($victor->id);

        return back()->with('success', 'Member removed.');
    }

    public function assignRun(Request $request, VictoryGamesEntry $entry)
    {
        $this->requireEntryOwner($entry);

        $data = $request->validate([
            'app_id' => 'nullable|exists:victory_games_apps,id',
        ]);

        $entry->update(['app_id' => $data['app_id']]);

        return back()->with('success', $data['app_id'] ? 'Run assigned to app.' : 'Run unassigned.');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function requireVictor(): VictoryGamesVictor
    {
        $user   = auth()->user();
        $victor = $user ? VictoryGamesVictor::where('user_id', $user->id)->first() : null;
        abort_unless($victor, 403, 'You need a victor profile to manage apps.');
        return $victor;
    }

    private function requireAppEdit(VictoryGamesApp $app): void
    {
        $user   = auth()->user();
        $victor = $user ? VictoryGamesVictor::where('user_id', $user->id)->first() : null;
        abort_unless(
            ($victor && $app->isMember($victor)) || ($user && $user->is_admin),
            403
        );
    }

    private function requireEntryOwner(VictoryGamesEntry $entry): void
    {
        $user   = auth()->user();
        $victor = $user ? VictoryGamesVictor::where('user_id', $user->id)->first() : null;
        abort_unless(
            ($victor && $entry->victor_id === $victor->id) || ($user && $user->is_admin),
            403
        );
    }

    private function serializeEntry(VictoryGamesEntry $entry): array
    {
        return [
            'id'              => $entry->id,
            'app_url'         => $entry->app_url,
            'app_hostname'    => $entry->appHostname(),
            'app_goal'        => $entry->app_goal,
            'placement'       => $entry->placement,
            'placement_label' => $entry->placementLabel(),
            'submission_note' => $entry->submission_note,
            'entry_profile'   => $entry->entry_profile,
            'step_count'      => $entry->steps->count(),
            'submitted_at'    => $entry->submitted_at?->toISOString(),
            'competition'     => $entry->competition ? [
                'id'      => $entry->competition->id,
                'slug'    => $entry->competition->slug,
                'name'    => $entry->competition->name,
                'held_at' => $entry->competition->held_at?->toISOString(),
            ] : null,
        ];
    }
}
