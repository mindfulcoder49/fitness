<?php

namespace App\Services\VictoryGames;

use App\Contracts\VictoryGames\BrowserSession;
use Playwright\Page\PageInterface;

class PlaywrightBrowserSession implements BrowserSession
{
    public function __construct(
        private readonly PageInterface $page,
        private readonly \Closure $closer,
    ) {}

    public function navigate(string $url): void
    {
        $this->page->goto($url, [
            'waitUntil' => 'domcontentloaded',
            'timeout' => (float) config('services.playwright.timeout_ms', 45000),
        ]);

        $this->settle();
    }

    public function executeJavaScript(string $script): mixed
    {
        $result = $this->page->evaluate(
            <<<'JS'
            async ({ script }) => {
                const normalize = (value) => {
                    if (value === undefined || value === null) return null;
                    if (typeof value === 'string') return value;

                    try {
                        return JSON.stringify(value);
                    } catch (_) {
                        return String(value);
                    }
                };

                const looksLikeSyntaxError = (error) => {
                    if (!error) return false;

                    const text = `${error.name || ''} ${error.message || ''}`.toLowerCase();

                    return (
                        text.includes('syntaxerror') ||
                        text.includes('illegal return') ||
                        text.includes('unexpected token') ||
                        text.includes('unexpected identifier') ||
                        text.includes('missing') ||
                        text.includes('unterminated')
                    );
                };

                try {
                    return normalize(await eval(script));
                } catch (expressionError) {
                    if (!looksLikeSyntaxError(expressionError)) {
                        throw expressionError;
                    }
                }

                const AsyncFunction = Object.getPrototypeOf(async function () {}).constructor;
                const fn = new AsyncFunction(script);

                return normalize(await fn());
            }
            JS,
            ['script' => $script],
        );

        $this->settle();

        return $result;
    }

    public function screenshot(string $outputPath): string
    {
        return $this->page->screenshot($outputPath, [
            'fullPage' => true,
            'type' => 'png',
        ]);
    }

    public function html(): string
    {
        return $this->page->content() ?? '';
    }

    public function url(): string
    {
        return $this->page->url();
    }

    public function close(): void
    {
        ($this->closer)();
    }

    private function settle(): void
    {
        try {
            $this->page->waitForLoadState('domcontentloaded', [
                'timeout' => (float) config('services.playwright.timeout_ms', 45000),
            ]);
        } catch (\Throwable) {
        }

        try {
            $this->page->waitForLoadState('networkidle', [
                'timeout' => 3000,
            ]);
        } catch (\Throwable) {
        }
    }
}
