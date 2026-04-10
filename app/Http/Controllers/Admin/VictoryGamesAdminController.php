<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\VictoryGamesWelcome;
use App\Models\VictoryGamesApp;
use App\Models\VictoryGamesCompetition;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesVictor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VictoryGamesAdminController extends Controller
{
    public function index()
    {
        $competitions = VictoryGamesCompetition::orderByDesc('held_at')->get()
            ->map(function ($comp) {
                $victorIds = VictoryGamesVictor::whereHas(
                    'entries', fn ($q) => $q->where('competition_id', $comp->id)
                )->pluck('id');

                return [
                    'id'           => $comp->id,
                    'slug'         => $comp->slug,
                    'name'         => $comp->name,
                    'held_at'      => $comp->held_at?->toISOString(),
                    'victor_count' => $victorIds->count(),
                    'with_email'   => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('email')->count(),
                    'claimed'      => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('user_id')->count(),
                    'emails_sent'  => VictoryGamesVictor::whereIn('id', $victorIds)->whereNotNull('welcome_email_sent_at')->count(),
                ];
            });

        return Inertia::render('Admin/VictoryGames', [
            'competitions' => $competitions,
        ]);
    }

    public function runs(Request $request)
    {
        $filter = $request->get('filter', 'all'); // all | no_victor | no_app

        $query = VictoryGamesEntry::with(['victor', 'app', 'competition'])
            ->orderByDesc('submitted_at');

        if ($filter === 'no_victor') {
            $query->whereNull('victor_id');
        } elseif ($filter === 'no_app') {
            $query->whereNull('app_id');
        }

        $entries = $query->get()->map(fn ($e) => [
            'id'           => $e->id,
            'app_url'      => $e->app_url,
            'app_hostname' => $e->appHostname(),
            'app_goal'     => $e->app_goal,
            'step_count'   => $e->steps()->count(),
            'submitted_at' => $e->submitted_at?->toISOString(),
            'victor_id'    => $e->victor_id,
            'victor'       => $e->victor ? [
                'id'           => $e->victor->id,
                'slug'         => $e->victor->slug,
                'display_name' => $e->victor->display_name,
            ] : null,
            'app_id'       => $e->app_id,
            'app'          => $e->app ? [
                'id'   => $e->app->id,
                'slug' => $e->app->slug,
                'name' => $e->app->name,
            ] : null,
            'competition'  => $e->competition ? [
                'id'   => $e->competition->id,
                'slug' => $e->competition->slug,
                'name' => $e->competition->name,
            ] : null,
        ]);

        $victors = VictoryGamesVictor::orderBy('display_name')->get()
            ->map(fn ($v) => ['id' => $v->id, 'display_name' => $v->display_name]);

        $apps = VictoryGamesApp::orderBy('name')->get()
            ->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'slug' => $a->slug]);

        return Inertia::render('Admin/VictoryGamesRuns', [
            'entries' => $entries,
            'victors' => $victors,
            'apps'    => $apps,
            'filter'  => $filter,
        ]);
    }

    public function updateRun(Request $request, VictoryGamesEntry $entry)
    {
        $data = $request->validate([
            'victor_id' => 'nullable|exists:victory_games_victors,id',
            'app_id'    => 'nullable|exists:victory_games_apps,id',
        ]);

        $entry->update($data);

        return back()->with('success', 'Run updated.');
    }

    public function deleteCompetition(VictoryGamesCompetition $competition)
    {
        $name = $competition->name;

        // Clean up screenshot files for all entries
        foreach ($competition->entries as $entry) {
            Storage::disk('public')->deleteDirectory("victory-games/screenshots/{$entry->id}");
        }

        $competition->delete(); // entries + steps cascade

        return redirect()->route('admin.victory-games.index')
            ->with('success', "Competition \"{$name}\" and all its runs have been deleted.");
    }

    public function sendWelcomeEmails(VictoryGamesCompetition $competition)
    {
        $victors = VictoryGamesVictor::whereHas(
            'entries', fn ($q) => $q->where('competition_id', $competition->id)
        )->whereNotNull('email')->get();

        $sent   = 0;
        $failed = 0;

        foreach ($victors as $victor) {
            try {
                Mail::to($victor->email)->send(new VictoryGamesWelcome($victor, $competition));
                $victor->update(['welcome_email_sent_at' => now()]);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('VictoryGames welcome email failed', [
                    'victor_id' => $victor->id,
                    'email'     => $victor->email,
                    'error'     => $e->getMessage(),
                ]);
            }
        }

        if ($failed > 0) {
            return back()->with('error', "Sent {$sent} welcome emails; {$failed} failed. Check logs for details.");
        }

        return back()->with('success', "Welcome emails sent to {$sent} victors.");
    }
}
