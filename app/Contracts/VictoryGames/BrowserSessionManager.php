<?php

namespace App\Contracts\VictoryGames;

interface BrowserSessionManager
{
    public function open(string $mode): BrowserSession;
}
