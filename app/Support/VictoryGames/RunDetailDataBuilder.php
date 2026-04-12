<?php

namespace App\Support\VictoryGames;

use App\Models\User;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;

class RunDetailDataBuilder
{
    public function build(VictoryGamesEntry $entry, ?User $user = null): array
    {
        $entry->load(['competition', 'victor', 'app', 'steps', 'logs']);

        $authVictor = $user?->victoryGamesVictor;
        $activeNativeRun = $entry->run_origin === 'native'
            && in_array($entry->session_status, ['queued', 'running', 'analyzing'], true);
        $canDelete = $user && (
            $user->is_admin
            || ($entry->competition_id === null && $entry->victor && $entry->victor->user_id === $user->id)
        ) && !$activeNativeRun;
        $canAssignApp = $user && (
            $user->is_admin
            || ($authVictor && $entry->victor_id === $authVictor->id)
        );
        $canStop = $user && $activeNativeRun && (
            $user->is_admin
            || ($entry->app && $authVictor && $entry->app->isMember($authVictor))
            || ($entry->victor && $entry->victor->user_id === $user->id)
        );

        $assignableApps = collect();

        if ($canAssignApp) {
            $assignableApps = $user->is_admin
                ? VictoryGamesApp::orderBy('name')->get(['id', 'slug', 'name'])
                : $authVictor->apps()->orderBy('name')->get(['victory_games_apps.id', 'victory_games_apps.slug', 'victory_games_apps.name']);
        }

        return [
            'entry' => [
                'id' => $entry->id,
                'app_url' => $entry->app_url,
                'app_hostname' => $entry->appHostname(),
                'app_goal' => $entry->app_goal,
                'app_mode' => $entry->app_mode,
                'run_origin' => $entry->run_origin,
                'session_provider' => $entry->session_provider,
                'session_model' => $entry->session_model,
                'session_status' => $entry->session_status,
                'started_at' => $entry->started_at?->toISOString(),
                'completed_at' => $entry->completed_at?->toISOString(),
                'end_reason' => $entry->end_reason,
                'placement' => $entry->placement,
                'placement_label' => $entry->placementLabel(),
                'submission_note' => $entry->submission_note,
                'entry_profile' => $entry->entry_profile,
                'postmortem' => [
                    'run_analysis' => $entry->postmortem_run_analysis,
                    'html_analysis' => $entry->postmortem_html_analysis,
                    'recommendations' => $entry->postmortem_recommendations,
                ],
                'competition_id' => $entry->competition_id,
                'poll_interval_seconds' => (int) config('victory_games.native_runs.poll_interval_seconds', 4),
            ],
            'competition' => $entry->competition ? [
                'id' => $entry->competition->id,
                'slug' => $entry->competition->slug,
                'name' => $entry->competition->name,
            ] : null,
            'app' => $entry->app ? [
                'id' => $entry->app->id,
                'slug' => $entry->app->slug,
                'name' => $entry->app->name,
            ] : null,
            'victor' => $entry->victor ? [
                'slug' => $entry->victor->slug,
                'display_name' => $entry->victor->display_name,
                'avatar_url' => $entry->victor->avatar_url,
            ] : null,
            'steps' => $entry->steps->map(fn ($step) => [
                'id' => $step->id,
                'step_number' => $step->step_number,
                'action_type' => $step->action_type,
                'action_params' => $step->action_params,
                'intent' => $step->intent,
                'reasoning' => $step->reasoning,
                'action_result' => $step->action_result,
                'success' => $step->success,
                'error_message' => $step->error_message,
                'page_url' => $step->page_url,
                'screenshot_url' => $step->screenshot_url,
                'timestamp' => $step->timestamp,
            ])->values()->all(),
            'logs' => $entry->logs->map(fn ($log) => [
                'id' => $log->id,
                'step_number' => $log->step_number,
                'level' => $log->level,
                'message' => $log->message,
                'details' => $log->details,
                'created_at' => $log->created_at?->toISOString(),
            ])->values()->all(),
            'canDelete' => $canDelete,
            'canAssignApp' => $canAssignApp,
            'canStop' => $canStop,
            'assignableApps' => $assignableApps->map(fn ($app) => [
                'id' => $app->id,
                'slug' => $app->slug,
                'name' => $app->name,
            ])->values()->all(),
        ];
    }
}
