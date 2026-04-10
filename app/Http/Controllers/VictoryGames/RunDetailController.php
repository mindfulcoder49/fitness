<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesEntry;
use Inertia\Inertia;

class RunDetailController extends Controller
{
    public function show(VictoryGamesEntry $entry)
    {
        $entry->load(['competition', 'victor', 'steps']);

        $steps = $entry->steps->map(fn ($step) => [
            'id'             => $step->id,
            'step_number'    => $step->step_number,
            'action_type'    => $step->action_type,
            'action_params'  => $step->action_params,
            'intent'         => $step->intent,
            'reasoning'      => $step->reasoning,
            'action_result'  => $step->action_result,
            'success'        => $step->success,
            'error_message'  => $step->error_message,
            'page_url'       => $step->page_url,
            'screenshot_url' => $step->screenshot_url,
            'timestamp'      => $step->timestamp,
        ]);

        return Inertia::render('VictoryGames/RunDetail', [
            'entry' => [
                'id'              => $entry->id,
                'app_url'         => $entry->app_url,
                'app_hostname'    => $entry->appHostname(),
                'app_goal'        => $entry->app_goal,
                'app_mode'        => $entry->app_mode,
                'session_provider'=> $entry->session_provider,
                'session_model'   => $entry->session_model,
                'placement'       => $entry->placement,
                'placement_label' => $entry->placementLabel(),
                'submission_note' => $entry->submission_note,
                'entry_profile'   => $entry->entry_profile,
                'postmortem'      => [
                    'run_analysis'    => $entry->postmortem_run_analysis,
                    'html_analysis'   => $entry->postmortem_html_analysis,
                    'recommendations' => $entry->postmortem_recommendations,
                ],
            ],
            'competition' => [
                'id'   => $entry->competition->id,
                'slug' => $entry->competition->slug,
                'name' => $entry->competition->name,
            ],
            'victor' => $entry->victor ? [
                'slug'         => $entry->victor->slug,
                'display_name' => $entry->victor->display_name,
                'avatar_url'   => $entry->victor->avatar_url,
            ] : null,
            'steps' => $steps,
        ]);
    }
}
