<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesEntry;
use App\Support\VictoryGames\RunDetailDataBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RunDetailController extends Controller
{
    public function __construct(private readonly RunDetailDataBuilder $runDetailDataBuilder) {}

    public function show(Request $request, VictoryGamesEntry $entry)
    {
        return Inertia::render('VictoryGames/RunDetail', $this->runDetailDataBuilder->build($entry, $request->user()));
    }

    public function destroy(Request $request, VictoryGamesEntry $entry)
    {
        $user = auth()->user();

        abort_unless($entry->canBeDeletedBy($user), 403, 'You can only delete your own completed runs.');

        $victorSlug = $entry->victor?->slug;
        $appSlug = $entry->app?->slug;
        $previousUrl = (string) url()->previous();
        $runUrl = route('victory-games.runs.show', $entry->id);

        Storage::disk('public')->deleteDirectory("victory-games/screenshots/{$entry->id}");

        $entry->delete();

        // If the user was on a page other than the run detail page itself, go back there.
        // Guard against the default fallback value ('/' or the app root) slipping through.
        $isRealPreviousPage = $previousUrl !== ''
            && $previousUrl !== url('/')
            && $previousUrl !== route('victory-games.home')
            && !Str::endsWith($previousUrl, "/victory-games/runs/{$entry->id}");

        if ($isRealPreviousPage) {
            return back()->with('success', 'Run deleted.');
        }

        if ($appSlug) {
            return redirect()->route('victory-games.apps.show', $appSlug)
                ->with('success', 'Run deleted.');
        }

        if ($victorSlug) {
            return redirect()->route('victory-games.victors.show', $victorSlug)
                ->with('success', 'Run deleted.');
        }

        return redirect()->route('victory-games.home')->with('success', 'Run deleted.');
    }
}
