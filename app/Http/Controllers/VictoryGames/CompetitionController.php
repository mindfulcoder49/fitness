<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesCompetition;
use Inertia\Inertia;

class CompetitionController extends Controller
{
    public function show(VictoryGamesCompetition $competition)
    {
        $entries = $competition->rankedEntries()->get()->map(fn ($entry) => [
            'id'               => $entry->id,
            'external_entry_id'=> $entry->external_entry_id,
            'app_url'          => $entry->app_url,
            'app_hostname'     => $entry->appHostname(),
            'app_goal'         => $entry->app_goal,
            'placement'        => $entry->placement,
            'placement_label'  => $entry->placementLabel(),
            'submission_note'  => $entry->submission_note,
            'entry_profile'    => $entry->entry_profile,
            'step_count'       => $entry->steps()->count(),
            'victor'           => $entry->victor ? [
                'slug'         => $entry->victor->slug,
                'display_name' => $entry->victor->display_name,
                'avatar_url'   => $entry->victor->avatar_url,
            ] : null,
        ]);

        $runs = $competition->runs()
            ->orderBy('run_number')
            ->with('matches')
            ->get()
            ->map(fn ($run) => [
                'id'                         => $run->id,
                'run_number'                 => $run->run_number,
                'status'                     => $run->status,
                'champion_entry_external_id' => $run->champion_entry_external_id,
                'pairing_strategy'           => $run->pairing_strategy,
                'provider'                   => $run->provider,
                'model'                      => $run->model,
                'completed_at'               => $run->completed_at?->toISOString(),
                'matches'                    => $run->matches->map(fn ($m) => [
                    'id'                       => $m->id,
                    'round_number'             => $m->round_number,
                    'match_number'             => $m->match_number,
                    'entry_external_ids'       => $m->entry_external_ids,
                    'winner_entry_external_id' => $m->winner_entry_external_id,
                    'judge_reasoning'          => $m->judge_reasoning,
                ])->values(),
            ]);

        return Inertia::render('VictoryGames/Competition', [
            'competition' => [
                'id'                => $competition->id,
                'slug'              => $competition->slug,
                'name'              => $competition->name,
                'description'       => $competition->description,
                'overall_narrative' => $competition->overall_narrative,
                'held_at'           => $competition->held_at?->toISOString(),
                'status'            => $competition->status,
            ],
            'entries' => $entries,
            'runs'    => $runs,
        ]);
    }
}
