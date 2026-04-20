<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AppController extends Controller
{
    // ── Public ─────────────────────────────────────────────────────────────────

    public function show(Request $request, VictoryGamesApp $app)
    {
        $app->load(['victors', 'entries.competition', 'entries.steps']);

        $authUser   = $request->user();
        $authVictor = $authUser ? VictoryGamesVictor::where('user_id', $authUser->id)->first() : null;
        $canEdit    = (($authVictor && $app->isOwnedBy($authVictor)) || ($authUser && $authUser->is_admin));
        $canRun     = ($authVictor && ($app->isMember($authVictor) || ($authUser && $authUser->is_admin)));

        $availableProviders = $this->availableRunProviders();
        $defaultProviderKey = array_key_first($availableProviders);
        $defaultProvider = $defaultProviderKey ? $availableProviders[$defaultProviderKey] : null;
        $runDefaults = [
            'start_url' => $app->current_url,
            'mode' => config('victory_games.native_runs.default_mode', 'desktop'),
            'provider' => $defaultProviderKey,
            'model' => $defaultProvider ? Arr::get($defaultProvider, 'default_model') : '',
            'max_steps' => (int) config('victory_games.native_runs.max_steps', 8),
        ];

        $entries = $app->entries()
            ->with(['competition', 'steps', 'victor'])
            ->orderByDesc('submitted_at')
            ->get()
            ->map(fn ($e) => $this->serializeEntry($e, $authUser, $runDefaults));

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
            'nativeRun' => [
                'canStart' => $canRun && !empty($availableProviders),
                'providers' => array_values(array_map(fn (string $key, array $provider) => [
                    'key' => $key,
                    'label' => Arr::get($provider, 'label', ucfirst($key)),
                    'default_model' => Arr::get($provider, 'default_model'),
                ], array_keys($availableProviders), $availableProviders)),
                'defaults' => $runDefaults,
                'limits' => [
                    'min_steps' => max(1, (int) config('victory_games.native_runs.min_steps', 1)),
                    'max_steps' => max(1, (int) config('victory_games.native_runs.max_steps_limit', 50)),
                ],
            ],
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

        if ($app->isMember($member)) {
            return back()->with('info', "{$member->display_name} is already on the team.");
        }

        $app->victors()->attach($member->id, ['role' => 'member']);

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

        $user = auth()->user();
        $victor = $user ? VictoryGamesVictor::where('user_id', $user->id)->first() : null;

        $data = $request->validate([
            'app_id' => 'nullable|exists:victory_games_apps,id',
        ]);

        if (!empty($data['app_id']) && !($user && $user->is_admin)) {
            abort_unless(
                $victor && $victor->apps()->whereKey($data['app_id'])->exists(),
                403,
                'You can only assign runs to your own apps.'
            );
        }

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
            ($victor && $app->isOwnedBy($victor)) || ($user && $user->is_admin),
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

    private function serializeEntry(VictoryGamesEntry $entry, ?User $user, array $runDefaults): array
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
            'run_origin'      => $entry->run_origin,
            'session_status'  => $entry->session_status,
            'run_config'      => $entry->run_config,
            'started_at'      => $entry->started_at?->toISOString(),
            'completed_at'    => $entry->completed_at?->toISOString(),
            'is_active_native_run' => $entry->isActiveNativeRun(),
            'can_delete'      => $entry->canBeDeletedBy($user),
            'reuse_config'    => $entry->reusableConfig($runDefaults),
            'competition'     => $entry->competition ? [
                'id'      => $entry->competition->id,
                'slug'    => $entry->competition->slug,
                'name'    => $entry->competition->name,
                'held_at' => $entry->competition->held_at?->toISOString(),
            ] : null,
        ];
    }

    private function availableRunProviders(): array
    {
        return collect(config('victory_games.native_runs.providers', []))
            ->filter(fn (array $provider) => (bool) Arr::get($provider, 'enabled'))
            ->all();
    }
}
