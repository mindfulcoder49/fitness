<?php

namespace App\Console\Commands;

use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesCertificate;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesMatch;
use App\Models\VictoryGamesVictor;
use App\Models\VictoryGamesRun;
use App\Models\VictoryGamesRunStep;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VictoryGamesImportCommand extends Command
{
    protected $signature   = 'victory-games:import {file : Path to the export JSON file} {--no-screenshots : Skip screenshot import}';
    protected $description = 'Import a competition export JSON into the VibeCode Victory Games platform';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }

        $this->info("Reading export from: {$file}");
        ini_set('memory_limit', '512M');

        $payload = json_decode(file_get_contents($file), true);
        if (!$payload || !isset($payload['competition'])) {
            $this->error('Invalid export payload.');
            return 1;
        }

        try {
            $result = DB::transaction(fn () => $this->import($payload));
            $this->info("Import complete!");
            $this->table(['Key', 'Value'], collect($result)->map(fn ($v, $k) => [$k, $v])->values()->toArray());
            return 0;
        } catch (\Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }

    private function import(array $payload): array
    {
        $comp      = $this->upsertCompetition($payload['competition'], $payload['recap'] ?? null);
        $entryMap  = [];

        foreach ($payload['entries'] ?? [] as $entryData) {
            $entry = $this->upsertEntry($comp, $entryData);
            $entryMap[$entryData['external_id']] = $entry;
        }

        foreach ($entryMap as $entry) {
            if (!$entry->victor_id) {
                $victor = VictoryGamesVictor::firstOrCreate(
                    ['external_user_id' => $entry->external_user_id],
                    [
                        'slug'         => $this->uniqueSlug($entry->appHostname()),
                        'display_name' => $entry->appHostname(),
                    ]
                );
                $entry->update(['victor_id' => $victor->id]);
            }
        }

        foreach ($payload['runs'] ?? [] as $runData) {
            $this->upsertRun($comp, $runData);
        }

        foreach ($entryMap as $entry) {
            $entry->refresh();
            if (!$entry->victor_id) continue;

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

        return [
            'competition'    => $comp->name,
            'competition_id' => $comp->id,
            'entries'        => count($entryMap),
            'runs'           => count($payload['runs'] ?? []),
            'victors'        => VictoryGamesVictor::count(),
            'certificates'   => VictoryGamesCertificate::where('competition_id', $comp->id)->count(),
        ];
    }

    private function upsertCompetition(array $data, ?array $recap): VictoryGamesCompetition
    {
        $slug = $this->uniqueSlugComp($data['name'], $data['external_id']);
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
        $session    = $data['session'] ?? [];
        $postmortem = $data['postmortem'] ?? null;
        $skipScreenshots = $this->option('no-screenshots');

        $entry = VictoryGamesEntry::updateOrCreate(
            ['competition_id' => $comp->id, 'external_entry_id' => $data['external_id']],
            [
                'external_user_id'           => $data['external_user_id'],
                'app_url'                    => $session['start_url'] ?? null,
                'app_goal'                   => $session['goal'] ?? null,
                'app_mode'                   => $session['mode'] ?? null,
                'session_provider'           => $session['provider'] ?? null,
                'session_model'              => $session['model'] ?? null,
                'session_status'             => $session['status'] ?? null,
                'session_external_id'        => $session['external_id'] ?? null,
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
            $stepRows = [];
            foreach ($data['steps'] as $stepData) {
                $screenshotPath = null;
                if (!$skipScreenshots && !empty($stepData['screenshot_b64'])) {
                    $png  = base64_decode($stepData['screenshot_b64']);
                    $path = "victory-games/screenshots/{$entry->id}/step_{$stepData['step_number']}.png";
                    Storage::disk('public')->put($path, $png);
                    $screenshotPath = $path;
                }
                $stepRows[] = [
                    'entry_id'        => $entry->id,
                    'step_number'     => $stepData['step_number'],
                    'action_type'     => $stepData['action_type'],
                    'action_params'   => isset($stepData['action_params']) ? json_encode($stepData['action_params']) : null,
                    'intent'          => $stepData['intent'] ?? null,
                    'reasoning'       => $stepData['reasoning'] ?? null,
                    'action_result'   => $stepData['action_result'] ?? null,
                    'success'         => $stepData['success'] ? 1 : 0,
                    'error_message'   => $stepData['error_message'] ?? null,
                    'page_url'        => $stepData['page_url'] ?? null,
                    'screenshot_path' => $screenshotPath,
                    'timestamp'       => $stepData['timestamp'] ?? null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }
            VictoryGamesRunStep::insert($stepRows);
        }

        return $entry->fresh();
    }

    private function upsertRun(VictoryGamesCompetition $comp, array $data): void
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
        $matchRows = [];
        foreach ($data['matches'] ?? [] as $m) {
            $matchRows[] = [
                'run_id'                   => $run->id,
                'external_match_id'        => $m['external_id'],
                'round_number'             => $m['round_number'],
                'match_number'             => $m['match_number'],
                'entry_external_ids'       => json_encode($m['entry_ids']),
                'winner_entry_external_id' => $m['winner_entry_id'] ?? null,
                'judge_reasoning'          => $m['judge_reasoning'] ?? null,
                'status'                   => $m['status'] ?? 'complete',
                'created_at'               => now(),
                'updated_at'               => now(),
            ];
        }
        if ($matchRows) VictoryGamesMatch::insert($matchRows);
    }

    private function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'victor';
        $slug = $base;
        $i    = 2;
        while (VictoryGamesVictor::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function uniqueSlugComp(string $source, string $externalId): string
    {
        $base = Str::slug($source) ?: 'competition';
        $slug = $base;
        $i    = 2;
        while (VictoryGamesCompetition::where('slug', $slug)->where('external_id', '!=', $externalId)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }
}
