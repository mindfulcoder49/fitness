<?php

namespace App\Contracts\VictoryGames;

interface BrowserSession
{
    public function navigate(string $url): void;

    public function executeJavaScript(string $script): mixed;

    public function screenshot(string $outputPath): string;

    public function html(): string;

    public function url(): string;

    public function close(): void;
}
