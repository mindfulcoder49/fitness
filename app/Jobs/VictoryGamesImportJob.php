<?php

namespace App\Jobs;

use App\Models\VictoryGamesCertificate;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesMatch;
use App\Models\VictoryGamesVictor;
use App\Models\VictoryGamesRun;
use App\Models\VictoryGamesRunStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VictoryGamesImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * How long the job can run before timing out (10 minutes).
     */
    public int $timeout = 600;

    /**
     * Number of times to attempt the job.
     */
    public int $tries = 1;

    public function __construct(private string $filePath) {}

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        $json = Storage::disk('local')->get($this->filePath);

        if (!$json) {
            Log::error('VictoryGamesImportJob: file not found', ['path' => $this->filePath]);
            return;
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['competition'])) {
            Log::error('VictoryGamesImportJob: invalid payload', ['path' => $this->filePath]);
            Storage::disk('local')->delete($this->filePath);
            return;
        }

        try {
            $result = DB::transaction(fn () => $this->import($payload));
            Log::info('VictoryGamesImportJob: complete', array_merge(['path' => $this->filePath], $result));
        } catch (\Throwable $e) {
            Log::error('VictoryGamesImportJob: failed', [
                'path'  => $this->filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        } finally {
            Storage::disk('local')->delete($this->filePath);
        }
    }

    private function import(array $payload): array
    {
        $comp = $this->upsertCompetition($payload['competition'], $payload['recap'] ?? null);

        $entryMap = [];
        $emailMap = [];
        foreach ($payload['entries'] ?? [] as $entryData) {
            $entry = $this->upsertEntry($comp, $entryData);
            $entryMap[$entryData['external_id']] = $entry;
            $emailMap[$entryData['external_id']] = $entryData['user_email'] ?? null;
        }

        foreach ($entryMap as $externalId => $entry) {
            $email = $emailMap[$externalId] ?? null;
            if (!$entry->victor_id) {
                $victor = VictoryGamesVictor::firstOrCreate(
                    ['external_user_id' => $entry->external_user_id],
                    [
                        'slug'         => $this->uniqueSlug($entry->appHostname()),
                        'display_name' => $entry->appHostname(),
                        'email'        => $email,
                        'claim_token'  => VictoryGamesVictor::generateClaimToken(),
                    ]
                );
                $entry->update(['victor_id' => $victor->id]);
            } else {
                $victor = VictoryGamesVictor::find($entry->victor_id);
                if ($victor) {
                    $updates = [];
                    if (!$victor->email && $email) $updates['email'] = $email;
                    if (!$victor->claim_token) $updates['claim_token'] = VictoryGamesVictor::generateClaimToken();
                    if ($updates) $victor->update($updates);
                }
            }
        }

        foreach ($payload['runs'] ?? [] as $runData) {
            $this->upsertRun($comp, $runData, $entryMap);
        }

        foreach ($entryMap as $entry) {
            if ($entry->victor_id) {
                $type = match ($entry->placement) {
                    1       => '1st',
                    2       => '2nd',
                    3       => '3rd',
                    default => 'participation',
                };
                VictoryGamesCertificate::firstOrCreate(
                    ['victor_id' => $entry->victor_id, 'competition_id' => $comp->id],
                    [
                        'certificate_type' => $type,
                        'download_token'   => VictoryGamesCertificate::generateToken(),
                        'issued_at'        => now(),
                    ]
                );
            }
        }

        return [
            'competition_id' => $comp->id,
            'competition'    => $comp->name,
            'entries'        => count($entryMap),
            'runs'           => count($payload['runs'] ?? []),
        ];
    }

    private function upsertCompetition(array $data, ?array $recap): VictoryGamesCompetition
    {
        $slug = $this->uniqueSlug($data['name'], VictoryGamesCompetition::class, $data['external_id']);

        return VictoryGamesCompetition::updateOrCreate(
            ['external_id' => $data['external_id']],
            [
                'slug'              => $slug,
                'name'              => $data['name'],
                'description'       => $data['description'] ?? null,
                'status'            => $data['status'] ?? 'complete',
                'held_at'           => $data['held_at'] ?? null,
                'overall_narrative' => $recap['overall_narrative'] ?? null,
                'recap_provider'    => $recap['provider'] ?? null,
                'recap_model'       => $recap['model'] ?? null,
            ]
        );
    }

    private function upsertEntry(VictoryGamesCompetition $comp, array $data): VictoryGamesEntry
    {
        $sessionData = $data['session'] ?? [];
        $postmortem  = $data['postmortem'] ?? null;

        $entry = VictoryGamesEntry::updateOrCreate(
            ['competition_id' => $comp->id, 'external_entry_id' => $data['external_id']],
            [
                'external_user_id'           => $data['external_user_id'],
                'app_url'                    => $sessionData['start_url'] ?? null,
                'app_goal'                   => $sessionData['goal'] ?? null,
                'app_mode'                   => $sessionData['mode'] ?? null,
                'session_provider'           => $sessionData['provider'] ?? null,
                'session_model'              => $sessionData['model'] ?? null,
                'session_status'             => $sessionData['status'] ?? null,
                'session_external_id'        => $sessionData['external_id'] ?? null,
                'submission_note'            => $data['note'] ?? null,
                'placement'                  => $data['placement'] ?? null,
                'entry_profile'              => $data['entry_profile'] ?: null,
                'postmortem_run_analysis'    => $postmortem['run_analysis'] ?? null,
                'postmortem_html_analysis'   => $postmortem['html_analysis'] ?? null,
                'postmortem_recommendations' => $postmortem['recommendations'] ?? null,
                'submitted_at'               => $data['submitted_at'] ?? null,
            ]
        );

        if (!empty($data['steps'])) {
            $entry->steps()->delete();
            foreach ($data['steps'] as $stepData) {
                $screenshotPath = null;
                if (!empty($stepData['screenshot_b64'])) {
                    $screenshotPath = $this->saveScreenshot(
                        $entry->id,
                        $stepData['step_number'],
                        $stepData['screenshot_b64']
                    );
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
        }

        return $entry->fresh();
    }

    private function upsertRun(VictoryGamesCompetition $comp, array $data, array $entryMap): void
    {
        $run = VictoryGamesRun::updateOrCreate(
            ['competition_id' => $comp->id, 'external_run_id' => $data['external_id']],
            [
                'run_number'                 => $data['run_number'],
                'status'                     => $data['status'],
                'champion_entry_external_id' => $data['champion_entry_id'] ?? null,
                'pairing_strategy'           => $data['pairing_strategy'] ?? null,
                'progression_mode'           => $data['progression_mode'] ?? null,
                'provider'                   => $data['provider'] ?? null,
                'model'                      => $data['model'] ?? null,
                'completed_at'               => $data['completed_at'] ?? null,
            ]
        );

        $run->matches()->delete();
        foreach ($data['matches'] ?? [] as $matchData) {
            VictoryGamesMatch::create([
                'run_id'                   => $run->id,
                'external_match_id'        => $matchData['external_id'],
                'round_number'             => $matchData['round_number'],
                'match_number'             => $matchData['match_number'],
                'entry_external_ids'       => $matchData['entry_ids'],
                'winner_entry_external_id' => $matchData['winner_entry_id'] ?? null,
                'judge_reasoning'          => $matchData['judge_reasoning'] ?? null,
                'status'                   => $matchData['status'] ?? 'complete',
            ]);
        }
    }

    private function saveScreenshot(int $entryId, int $stepNumber, string $b64): string
    {
        $png  = base64_decode($b64);
        $path = "victory-games/screenshots/{$entryId}/step_{$stepNumber}.png";
        Storage::disk('public')->put($path, $png);
        return $path;
    }

    private function uniqueSlug(string $source, string $model = VictoryGamesVictor::class, ?string $existingExternalId = null): string
    {
        $base = Str::slug($source) ?: 'victor';
        $slug = $base;
        $i    = 2;

        while (true) {
            $query = $model::where('slug', $slug);
            if ($existingExternalId) {
                $query->where('external_id', '!=', $existingExternalId);
            }
            if (!$query->exists()) {
                break;
            }
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
