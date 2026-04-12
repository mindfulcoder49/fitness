<?php

namespace Tests\Unit\VictoryGames;

use App\Services\VictoryGames\PlaywrightBrowserSessionManager;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PlaywrightBrowserSessionManagerTest extends TestCase
{
    public function test_build_environment_includes_browser_path_and_prepends_library_path(): void
    {
        $libraryPath = storage_path('framework/testing/playwright-libs');
        File::ensureDirectoryExists($libraryPath);

        config()->set('services.playwright.browsers_path', '/tmp/playwright-browsers');
        config()->set('services.playwright.library_path', $libraryPath);

        $originalLibraryPath = getenv('LD_LIBRARY_PATH');
        putenv('LD_LIBRARY_PATH=/existing/libs');

        try {
            $environment = (new PlaywrightBrowserSessionManager())->buildEnvironment();
        } finally {
            if ($originalLibraryPath === false) {
                putenv('LD_LIBRARY_PATH');
            } else {
                putenv('LD_LIBRARY_PATH='.$originalLibraryPath);
            }
        }

        $this->assertSame('/tmp/playwright-browsers', $environment['PLAYWRIGHT_BROWSERS_PATH']);
        $this->assertSame($libraryPath.':/existing/libs', $environment['LD_LIBRARY_PATH']);
    }
}
