<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesEntry;
use App\Support\VictoryGames\RunDetailDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RunDetailController extends Controller
{
    public function __construct(private readonly RunDetailDataBuilder $runDetailDataBuilder) {}

    public function show(Request $request, VictoryGamesEntry $entry)
    {
        return Inertia::render('VictoryGames/RunDetail', $this->runDetailDataBuilder->build($entry, $request->user()));
    }

    public function destroy(VictoryGamesEntry $entry)
    {
        $user = auth()->user();

        if ($entry->run_origin === 'native' && in_array($entry->session_status, ['queued', 'running', 'analyzing'], true)) {
            abort(403, 'Stop the active native run before deleting it.');
        }

        if ($user->is_admin) {
            // Admin can delete any run
        } elseif ($entry->competition_id !== null) {
            abort(403, 'Competition runs can only be deleted by an admin.');
        } elseif (!$entry->victor || $entry->victor->user_id !== $user->id) {
            abort(403, 'You can only delete your own runs.');
        }

        $victorSlug = $entry->victor?->slug;

        Storage::disk('public')->deleteDirectory("victory-games/screenshots/{$entry->id}");

        $entry->delete();

        if ($victorSlug) {
            return redirect()->route('victory-games.victors.show', $victorSlug)
                ->with('success', 'Run deleted.');
        }

        return redirect()->route('victory-games.home')->with('success', 'Run deleted.');
    }
}
