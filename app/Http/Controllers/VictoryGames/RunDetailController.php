<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesEntry;
use Illuminate\Support\Facades\Storage;
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

        $entry->load(['competition', 'victor', 'app', 'steps']);

        $user     = auth()->user();
        $canDelete = $user && (
            $user->is_admin
            || ($entry->competition_id === null && $entry->victor && $entry->victor->user_id === $user->id)
        );

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
                'competition_id'  => $entry->competition_id,
            ],
            'canDelete' => $canDelete,
            'competition' => $entry->competition ? [
                'id'   => $entry->competition->id,
                'slug' => $entry->competition->slug,
                'name' => $entry->competition->name,
            ] : null,
            'app' => $entry->app ? [
                'id'   => $entry->app->id,
                'slug' => $entry->app->slug,
                'name' => $entry->app->name,
            ] : null,
            'victor' => $entry->victor ? [
                'slug'         => $entry->victor->slug,
                'display_name' => $entry->victor->display_name,
                'avatar_url'   => $entry->victor->avatar_url,
            ] : null,
            'steps' => $steps,
        ]);
    }

    public function destroy(VictoryGamesEntry $entry)
    {
        $user = auth()->user();

        if ($user->is_admin) {
            // Admin can delete any run
        } elseif ($entry->competition_id !== null) {
            abort(403, 'Competition runs can only be deleted by an admin.');
        } elseif (!$entry->victor || $entry->victor->user_id !== $user->id) {
            abort(403, 'You can only delete your own runs.');
        }

        $victorSlug = $entry->victor?->slug;

        // Clean up stored screenshots
        Storage::disk('public')->deleteDirectory("victory-games/screenshots/{$entry->id}");

        $entry->delete(); // steps cascade

        if ($victorSlug) {
            return redirect()->route('victory-games.victors.show', $victorSlug)
                ->with('success', 'Run deleted.');
        }

        return redirect()->route('victory-games.home')->with('success', 'Run deleted.');
    }
}
