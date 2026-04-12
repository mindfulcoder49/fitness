<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Jobs\RunVictoryGamesNativeAiuxJob;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use App\Support\VictoryGames\RunDetailDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class NativeRunController extends Controller
{
    public function __construct(private readonly RunDetailDataBuilder $runDetailDataBuilder) {}

    public function store(Request $request, VictoryGamesApp $app)
    {
        $victor = $this->requireVictor();
        abort_unless($app->isMember($victor) || $request->user()?->is_admin, 403);

        $availableProviders = $this->availableProviders();

        $data = $request->validate([
            'goal' => 'required|string|max:5000',
            'start_url' => 'nullable|url|max:500',
            'mode' => 'required|in:desktop,mobile',
            'provider' => 'required|string|in:'.implode(',', array_keys($availableProviders)),
            'model' => 'required|string|max:255',
        ]);

        $startUrl = $data['start_url'] ?: $app->current_url;
        abort_unless(is_string($startUrl) && trim($startUrl) !== '', 422, 'A start URL is required.');

        $entry = VictoryGamesEntry::create([
            'competition_id' => null,
            'app_id' => $app->id,
            'victor_id' => $victor->id,
            'started_by_user_id' => $request->user()->id,
            'external_entry_id' => (string) Str::ulid(),
            'external_user_id' => $victor->external_user_id ?: 'native-user-'.$request->user()->id,
            'run_origin' => 'native',
            'app_url' => $startUrl,
            'app_goal' => $data['goal'],
            'app_mode' => $data['mode'],
            'session_provider' => $data['provider'],
            'session_model' => $data['model'],
            'session_status' => 'queued',
            'session_external_id' => (string) Str::uuid(),
            'run_config' => [
                'max_steps' => (int) config('victory_games.native_runs.max_steps', 8),
                'loop_detection_window' => (int) config('victory_games.native_runs.loop_detection_window', 10),
                'loop_detection_rules' => config('victory_games.native_runs.loop_detection_rules', []),
            ],
            'submitted_at' => now(),
        ]);

        RunVictoryGamesNativeAiuxJob::dispatch($entry->id);

        return redirect()
            ->route('victory-games.runs.show', $entry)
            ->with('success', 'Native AIUX run queued.');
    }

    public function stop(Request $request, VictoryGamesEntry $entry)
    {
        abort_unless($entry->run_origin === 'native', 404);

        $user = $request->user();
        $victor = $user?->victoryGamesVictor;

        abort_unless(
            $user && (
                $user->is_admin
                || ($entry->victor && $entry->victor->user_id === $user->id)
                || ($entry->app && $victor && $entry->app->isMember($victor))
            ),
            403
        );

        if (in_array($entry->session_status, ['queued', 'running', 'analyzing'], true)) {
            $entry->forceFill([
                'session_status' => 'stopped',
                'end_reason' => 'Stop requested by user.',
            ])->save();
        }

        return back()->with('success', 'Run stop requested.');
    }

    public function status(Request $request, VictoryGamesEntry $entry)
    {
        return response()->json(
            $this->runDetailDataBuilder->build($entry->fresh(['competition', 'victor', 'app', 'steps', 'logs']), $request->user())
        );
    }

    private function requireVictor(): VictoryGamesVictor
    {
        $user = auth()->user();
        $victor = $user?->victoryGamesVictor;
        abort_unless($victor, 403, 'You need a victor profile before you can run native AIUX tests.');

        return $victor;
    }

    private function availableProviders(): array
    {
        return collect(config('victory_games.native_runs.providers', []))
            ->filter(fn (array $provider) => (bool) Arr::get($provider, 'enabled'))
            ->all();
    }
}
