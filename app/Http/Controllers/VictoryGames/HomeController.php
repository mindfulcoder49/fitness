<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesCompetition;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function __invoke()
    {
        $competitions = VictoryGamesCompetition::orderByDesc('held_at')
            ->withCount('entries')
            ->get()
            ->map(function ($competition) {
                $champion = $competition->entries()
                    ->where('placement', 1)
                    ->with('victor')
                    ->first();

                return [
                    'id'              => $competition->id,
                    'slug'            => $competition->slug,
                    'name'            => $competition->name,
                    'description'     => $competition->description,
                    'held_at'         => $competition->held_at?->toISOString(),
                    'entries_count'   => $competition->entries_count,
                    'champion'        => $champion ? [
                        'app_url'      => $champion->app_url,
                        'app_hostname' => $champion->appHostname(),
                        'victor'       => $champion->victor ? [
                            'slug'         => $champion->victor->slug,
                            'display_name' => $champion->victor->display_name,
                            'avatar_url'   => $champion->victor->avatar_url,
                        ] : null,
                    ] : null,
                ];
            });

        return Inertia::render('VictoryGames/Index', [
            'competitions' => $competitions,
        ]);
    }
}
