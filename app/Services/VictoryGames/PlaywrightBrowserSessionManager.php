<?php

namespace App\Services\VictoryGames;

use App\Contracts\VictoryGames\BrowserSession;
use App\Contracts\VictoryGames\BrowserSessionManager;
use Illuminate\Support\Facades\File;
use Playwright\Browser\BrowserContextBuilder;
use Playwright\Configuration\PlaywrightConfigBuilder;
use Playwright\PlaywrightFactory;

class PlaywrightBrowserSessionManager implements BrowserSessionManager
{
    public function open(string $mode): BrowserSession
    {
        $configBuilder = PlaywrightConfigBuilder::fromEnv()
            ->withHeadless(true)
            ->withTimeoutMs((int) config('services.playwright.timeout_ms', 45000))
            ->withArgs([
                '--no-sandbox',
                '--disable-setuid-sandbox',
                '--disable-dev-shm-usage',
                '--disable-gpu',
                '--no-zygote',
                '--single-process',
            ]);

        if ($nodePath = config('services.playwright.node_path')) {
            $configBuilder->withNodePath((string) $nodePath);
        }

        foreach ($this->buildEnvironment() as $key => $value) {
            $configBuilder->addEnv($key, $value);
        }

        $client = PlaywrightFactory::create($configBuilder->build());
        $browser = $client->chromium()->launch();

        $context = $browser->newContext($this->buildContextOptions($mode));
        $page = $context->newPage();
        $page->setViewportSize(
            (int) config('services.playwright.viewport_width', 1440),
            (int) config('services.playwright.viewport_height', 960),
        );

        return new PlaywrightBrowserSession(
            $page,
            function () use ($browser, $client): void {
                try {
                    $browser->close();
                } catch (\Throwable) {
                }

                $client->close();
            },
        );
    }

    public function buildEnvironment(): array
    {
        $environment = [];

        if ($browsersPath = config('services.playwright.browsers_path')) {
            $environment['PLAYWRIGHT_BROWSERS_PATH'] = (string) $browsersPath;
        }

        if ($libraryPath = $this->resolveLibraryPath()) {
            $environment['LD_LIBRARY_PATH'] = $libraryPath;
        }

        return $environment;
    }

    private function buildContextOptions(string $mode): array
    {
        if ($mode === 'mobile') {
            return BrowserContextBuilder::create()
                ->withViewport(390, 844)
                ->withUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 18_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.2 Mobile/15E148 Safari/604.1')
                ->withIsMobile()
                ->withHasTouch()
                ->withDeviceScaleFactor(3)
                ->toArray();
        }

        return BrowserContextBuilder::create()
            ->withViewport(
                (int) config('services.playwright.viewport_width', 1440),
                (int) config('services.playwright.viewport_height', 960),
            )
            ->withUserAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36')
            ->toArray();
    }

    private function resolveLibraryPath(): ?string
    {
        $configuredPath = config('services.playwright.library_path');

        if (!is_string($configuredPath) || trim($configuredPath) === '' || !File::isDirectory($configuredPath)) {
            return null;
        }

        $paths = [trim($configuredPath)];
        $currentLibraryPath = getenv('LD_LIBRARY_PATH');

        if (is_string($currentLibraryPath) && trim($currentLibraryPath) !== '') {
            foreach (explode(':', $currentLibraryPath) as $path) {
                $path = trim($path);

                if ($path !== '') {
                    $paths[] = $path;
                }
            }
        }

        return implode(':', array_values(array_unique($paths)));
    }
}
