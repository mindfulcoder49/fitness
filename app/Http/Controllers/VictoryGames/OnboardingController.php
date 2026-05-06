<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Jobs\RunVictoryGamesNativeAiuxJob;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->victoryGamesVictor) {
            return redirect()->route('victory-games.victors.show', $user->victoryGamesVictor->slug);
        }

        $providers = $this->availableProviders();
        $defaultProviderKey = array_key_first($providers);
        $defaultProvider = $defaultProviderKey ? $providers[$defaultProviderKey] : null;

        return Inertia::render('VictoryGames/Onboarding', [
            'providers' => array_values(array_map(
                fn (string $key, array $p) => [
                    'key' => $key,
                    'label' => Arr::get($p, 'label', ucfirst($key)),
                    'default_model' => Arr::get($p, 'default_model'),
                ],
                array_keys($providers),
                $providers
            )),
            'defaultProvider' => $defaultProviderKey,
            'defaultModel' => $defaultProvider ? Arr::get($defaultProvider, 'default_model') : null,
            'defaultMode' => config('victory_games.native_runs.default_mode', 'desktop'),
            'maxSteps' => (int) config('victory_games.native_runs.max_steps', 8),
        ]);
    }

    public function storeVictor(Request $request)
    {
        $user = $request->user();

        if ($user->victoryGamesVictor) {
            return response()->json(['victor' => [
                'slug' => $user->victoryGamesVictor->slug,
                'display_name' => $user->victoryGamesVictor->display_name,
            ]]);
        }

        $data = $request->validate(['display_name' => 'required|string|max:255']);

        $victor = VictoryGamesVictor::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'claim_token' => VictoryGamesVictor::generateClaimToken(),
            'slug' => VictoryGamesVictor::generateSlug($data['display_name']),
            'display_name' => $data['display_name'],
        ]);

        return response()->json(['victor' => [
            'slug' => $victor->slug,
            'display_name' => $victor->display_name,
        ]]);
    }

    public function storeApp(Request $request)
    {
        $user = $request->user();
        $victor = $user->victoryGamesVictor;
        abort_unless($victor, 422, 'Complete your profile first.');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'current_url' => 'required|url|max:500',
        ]);

        $app = VictoryGamesApp::create([
            'name' => $data['name'],
            'slug' => VictoryGamesApp::generateSlug($data['name']),
            'current_url' => $data['current_url'],
        ]);

        $app->victors()->attach($victor->id, ['role' => 'owner']);

        return response()->json(['app' => [
            'slug' => $app->slug,
            'name' => $app->name,
            'current_url' => $app->current_url,
        ]]);
    }

    public function storeRun(Request $request)
    {
        $user = $request->user();
        $victor = $user->victoryGamesVictor;
        abort_unless($victor, 403);

        $availableProviders = $this->availableProviders();
        $minSteps = max(1, (int) config('victory_games.native_runs.min_steps', 1));
        $maxStepsLimit = max($minSteps, (int) config('victory_games.native_runs.max_steps_limit', 50));

        $data = $request->validate([
            'app_slug'  => 'required|string|exists:victory_games_apps,slug',
            'goal'      => 'required|string|max:5000',
            'start_url' => 'nullable|url|max:500',
            'mode'      => 'required|in:desktop,mobile',
            'provider'  => 'required|string|in:' . implode(',', array_keys($availableProviders)),
            'model'     => 'required|string|max:255',
            'max_steps' => 'required|integer|min:' . $minSteps . '|max:' . $maxStepsLimit,
        ]);

        $app = VictoryGamesApp::where('slug', $data['app_slug'])->firstOrFail();
        abort_unless($app->isMember($victor), 403);

        $startUrl = $data['start_url'] ?: $app->current_url;
        abort_unless(is_string($startUrl) && trim($startUrl) !== '', 422, 'A start URL is required.');

        $entry = VictoryGamesEntry::create([
            'competition_id'      => null,
            'app_id'              => $app->id,
            'victor_id'           => $victor->id,
            'started_by_user_id'  => $user->id,
            'external_entry_id'   => (string) Str::ulid(),
            'external_user_id'    => $victor->external_user_id ?: 'native-user-' . $user->id,
            'run_origin'          => 'native',
            'app_url'             => $startUrl,
            'app_goal'            => $data['goal'],
            'app_mode'            => $data['mode'],
            'session_provider'    => $data['provider'],
            'session_model'       => $data['model'],
            'session_status'      => 'queued',
            'session_external_id' => (string) Str::uuid(),
            'run_config'          => [
                'max_steps'            => (int) $data['max_steps'],
                'loop_detection_window' => (int) config('victory_games.native_runs.loop_detection_window', 10),
                'loop_detection_rules'  => config('victory_games.native_runs.loop_detection_rules', []),
            ],
            'submitted_at' => now(),
        ]);

        RunVictoryGamesNativeAiuxJob::dispatch($entry->id);

        return redirect()
            ->route('victory-games.runs.show', $entry)
            ->with('success', 'Your first test is running!');
    }

    public function skip(Request $request)
    {
        $request->session()->put('onboarding_dismissed', true);

        return redirect()->route('dashboard');
    }

    private function availableProviders(): array
    {
        return collect(config('victory_games.native_runs.providers', []))
            ->filter(fn (array $p) => (bool) Arr::get($p, 'enabled'))
            ->all();
    }
}
