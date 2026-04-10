<?php

namespace App\Jobs;

use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesRunStep;
use App\Models\VictoryGamesVictor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VictoryGamesAppRunImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(private string $filePath) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $json = Storage::disk('local')->get($this->filePath);
        if (!$json) {
            Log::error('VictoryGamesAppRunImportJob: file not found', ['path' => $this->filePath]);
            return;
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['session'])) {
            Log::error('VictoryGamesAppRunImportJob: invalid payload', ['path' => $this->filePath]);
            Storage::disk('local')->delete($this->filePath);
            return;
        }

        try {
            $result = DB::transaction(fn () => $this->import($payload));
            Log::info('VictoryGamesAppRunImportJob: complete', array_merge(['path' => $this->filePath], $result));
        } catch (\Throwable $e) {
            Log::error('VictoryGamesAppRunImportJob: failed', [
                'path'  => $this->filePath,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filePath);
        }
    }

    private function import(array $payload): array
    {
        // Resolve victor by email or external_user_id
        $victor = null;
        if (!empty($payload['user_email'])) {
            $victor = VictoryGamesVictor::where('email', $payload['user_email'])->first();
        }
        if (!$victor && !empty($payload['external_user_id'])) {
            $victor = VictoryGamesVictor::where('external_user_id', $payload['external_user_id'])->first();
        }

        // Find or create the app by URL + victor
        $appUrl  = $payload['session']['start_url'] ?? null;
        $appName = $payload['app_name'] ?? ($appUrl ? $this->hostnameFromUrl($appUrl) : 'Unnamed App');

        $app = null;
        if ($victor) {
            // Try to find an existing app this victor owns/is member of with this URL
            $app = $victor->apps()
                ->where('current_url', $appUrl)
                ->first();

            if (!$app && $appUrl) {
                // Find by slug derived from the submitted app_name
                $app = VictoryGamesApp::whereHas('victors', fn ($q) => $q->where('victor_id', $victor->id))
                    ->where('name', $appName)
                    ->first();
            }

            if (!$app) {
                $app = VictoryGamesApp::create([
                    'name'        => $appName,
                    'slug'        => VictoryGamesApp::generateSlug($appName),
                    'description' => $payload['app_description'] ?? null,
                    'current_url' => $appUrl,
                ]);
                $app->victors()->attach($victor->id, ['role' => 'owner']);
            }
        }

        // Create the entry (no competition_id)
        $entry = VictoryGamesEntry::create([
            'competition_id'             => null,
            'app_id'                     => $app?->id,
            'victor_id'                  => $victor?->id,
            'external_entry_id'          => $payload['external_session_id'] ?? null,
            'external_user_id'           => $payload['external_user_id'] ?? null,
            'app_url'                    => $appUrl,
            'app_goal'                   => $payload['session']['goal'] ?? null,
            'app_mode'                   => $payload['session']['mode'] ?? null,
            'session_provider'           => $payload['session']['provider'] ?? null,
            'session_model'              => $payload['session']['model'] ?? null,
            'session_status'             => $payload['session']['status'] ?? null,
            'session_external_id'        => $payload['session']['external_id'] ?? null,
            'postmortem_run_analysis'    => $payload['postmortem']['run_analysis'] ?? null,
            'postmortem_html_analysis'   => $payload['postmortem']['html_analysis'] ?? null,
            'postmortem_recommendations' => $payload['postmortem']['recommendations'] ?? null,
            'submitted_at'               => $payload['exported_at'] ?? now(),
        ]);

        // Import steps
        foreach ($payload['steps'] ?? [] as $stepData) {
            $screenshotPath = null;
            if (!empty($stepData['screenshot_b64'])) {
                $png  = base64_decode($stepData['screenshot_b64']);
                $screenshotPath = "victory-games/screenshots/{$entry->id}/step_{$stepData['step_number']}.png";
                Storage::disk('public')->put($screenshotPath, $png);
            }

            VictoryGamesRunStep::create([
                'entry_id'        => $entry->id,
                'step_number'     => $stepData['step_number'],
                'action_type'     => $stepData['action_type'],
                'action_params'   => $stepData['action_params'] ?? null,
                'intent'          => $stepData['intent'] ?? null,
                'reasoning'       => $stepData['reasoning'] ?? null,
                'action_result'   => $stepData['action_result'] ?? null,
                'success'         => $stepData['success'] ?? true,
                'error_message'   => $stepData['error_message'] ?? null,
                'page_url'        => $stepData['page_url'] ?? null,
                'screenshot_path' => $screenshotPath,
                'timestamp'       => $stepData['timestamp'] ?? null,
            ]);
        }

        return [
            'entry_id' => $entry->id,
            'app_id'   => $app?->id,
            'app_name' => $app?->name,
            'victor'   => $victor?->display_name,
            'steps'    => count($payload['steps'] ?? []),
        ];
    }

    private function hostnameFromUrl(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
