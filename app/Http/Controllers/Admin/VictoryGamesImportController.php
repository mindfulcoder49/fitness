<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesCertificate;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesMatch;
use App\Models\VictoryGamesVictor;
use App\Models\VictoryGamesRun;
use App\Models\VictoryGamesRunStep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VictoryGamesImportController extends Controller
{
    /**
     * Accept a competition export JSON file and import it.
     *
     * Supports both:
     *   - multipart file upload:  POST with file field "export_file"
     *   - raw JSON body:          POST with Content-Type: application/json
     *
     * Idempotent: re-importing the same competition updates existing records.
     */
    public function store(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // Parse payload — either uploaded file or raw JSON body
        if ($request->hasFile('export_file')) {
            $json = $request->file('export_file')->get();
        } else {
            $json = $request->getContent();
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['competition'])) {
            return response()->json(['error' => 'Invalid export payload'], 422);
        }

        try {
            $result = DB::transaction(fn () => $this->import($payload));
            return response()->json(['ok' => true, ...$result]);
        } catch (\Throwable $e) {
            Log::error('VictoryGames import failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function import(array $payload): array
    {
        $comp = $this->upsertCompetition($payload['competition'], $payload['recap'] ?? null);

        // Build a map of external_entry_id → VictoryGamesEntry for the run/match import
        // Also track user_email per entry for victor creation below
        $entryMap = [];
        $emailMap = []; // external_entry_id => user_email
        foreach ($payload['entries'] ?? [] as $entryData) {
            $entry = $this->upsertEntry($comp, $entryData);
            $entryMap[$entryData['external_id']] = $entry;
            $emailMap[$entryData['external_id']] = $entryData['user_email'] ?? null;
        }

        // Link victor profiles to entries (match by external_user_id)
        foreach ($entryMap as $externalId => $entry) {
            $email = $emailMap[$externalId] ?? null;
            if (!$entry->victor_id) {
                $victor = VictoryGamesVictor::firstOrCreate(
                    ['external_user_id' => $entry->external_user_id],
                    [
                        'slug'        => $this->uniqueSlug($entry->appHostname()),
                        'display_name'=> $entry->appHostname(),
                        'email'       => $email,
                        'claim_token' => VictoryGamesVictor::generateClaimToken(),
                    ]
                );
                $entry->update(['victor_id' => $victor->id]);
            } else {
                // Back-fill email and claim_token if victor exists but is missing them
                $victor = VictoryGamesVictor::find($entry->victor_id);
                if ($victor) {
                    $updates = [];
                    if (!$victor->email && $email) $updates['email'] = $email;
                    if (!$victor->claim_token) $updates['claim_token'] = VictoryGamesVictor::generateClaimToken();
                    if ($updates) $victor->update($updates);
                }
            }
        }

        // Runs and matches
        foreach ($payload['runs'] ?? [] as $runData) {
            $this->upsertRun($comp, $runData, $entryMap);
        }

        // Issue certificates for placed entries (idempotent)
        foreach ($entryMap as $entry) {
            if ($entry->placement && $entry->victor_id) {
                $type = match ($entry->placement) {
                    1 => '1st',
                    2 => '2nd',
                    3 => '3rd',
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
            // Participation certificate for everyone
            if (!$entry->placement && $entry->victor_id) {
                VictoryGamesCertificate::firstOrCreate(
                    ['victor_id' => $entry->victor_id, 'competition_id' => $comp->id],
                    [
                        'certificate_type' => 'participation',
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

        $comp = VictoryGamesCompetition::updateOrCreate(
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

        return $comp;
    }

    private function upsertEntry(VictoryGamesCompetition $comp, array $data): VictoryGamesEntry
    {
        $sessionData = $data['session'] ?? [];
        $postmortem  = $data['postmortem'] ?? null;

        $entry = VictoryGamesEntry::updateOrCreate(
            ['competition_id' => $comp->id, 'external_entry_id' => $data['external_id']],
            [
                'external_user_id'             => $data['external_user_id'],
                'app_url'                      => $sessionData['start_url'] ?? null,
                'app_goal'                     => $sessionData['goal'] ?? null,
                'app_mode'                     => $sessionData['mode'] ?? null,
                'session_provider'             => $sessionData['provider'] ?? null,
                'session_model'                => $sessionData['model'] ?? null,
                'session_status'               => $sessionData['status'] ?? null,
                'session_external_id'          => $sessionData['external_id'] ?? null,
                'submission_note'              => $data['note'] ?? null,
                'placement'                    => $data['placement'] ?? null,
                'entry_profile'                => $data['entry_profile'] ?: null,
                'postmortem_run_analysis'      => $postmortem['run_analysis'] ?? null,
                'postmortem_html_analysis'     => $postmortem['html_analysis'] ?? null,
                'postmortem_recommendations'   => $postmortem['recommendations'] ?? null,
                'submitted_at'                 => $data['submitted_at'] ?? null,
            ]
        );

        // Import steps (delete & re-insert to handle re-imports cleanly)
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
                    'entry_id'      => $entry->id,
                    'step_number'   => $stepData['step_number'],
                    'action_type'   => $stepData['action_type'],
                    'action_params' => $stepData['action_params'] ?? null,
                    'intent'        => $stepData['intent'] ?? null,
                    'reasoning'     => $stepData['reasoning'] ?? null,
                    'action_result' => $stepData['action_result'] ?? null,
                    'success'       => $stepData['success'] ?? true,
                    'error_message' => $stepData['error_message'] ?? null,
                    'page_url'      => $stepData['page_url'] ?? null,
                    'screenshot_path' => $screenshotPath,
                    'timestamp'     => $stepData['timestamp'] ?? null,
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
                'run_number'                  => $data['run_number'],
                'status'                      => $data['status'],
                'champion_entry_external_id'  => $data['champion_entry_id'] ?? null,
                'pairing_strategy'            => $data['pairing_strategy'] ?? null,
                'progression_mode'            => $data['progression_mode'] ?? null,
                'provider'                    => $data['provider'] ?? null,
                'model'                       => $data['model'] ?? null,
                'completed_at'               => $data['completed_at'] ?? null,
            ]
        );

        // Re-insert matches
        $run->matches()->delete();
        foreach ($data['matches'] ?? [] as $matchData) {
            VictoryGamesMatch::create([
                'run_id'                    => $run->id,
                'external_match_id'         => $matchData['external_id'],
                'round_number'              => $matchData['round_number'],
                'match_number'              => $matchData['match_number'],
                'entry_external_ids'        => $matchData['entry_ids'],
                'winner_entry_external_id'  => $matchData['winner_entry_id'] ?? null,
                'judge_reasoning'           => $matchData['judge_reasoning'] ?? null,
                'status'                    => $matchData['status'] ?? 'complete',
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

    /**
     * Generate a URL-safe slug that is unique within the given model class.
     * If `$existingExternalId` is provided, the existing record with that external_id
     * is excluded from the uniqueness check (so re-importing doesn't conflict with itself).
     */
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
