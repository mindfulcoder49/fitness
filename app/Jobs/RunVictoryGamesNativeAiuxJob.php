<?php

namespace App\Jobs;

use App\Models\VictoryGamesEntry;
use App\Services\VictoryGames\NativeAiuxRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunVictoryGamesNativeAiuxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200;

    public int $tries = 1;

    public function __construct(private readonly int $entryId) {}

    public function handle(NativeAiuxRunner $runner): void
    {
        $entry = VictoryGamesEntry::find($this->entryId);

        if (!$entry) {
            return;
        }

        $runner->run($entry);
    }
}
