<?php

namespace App\Providers;

use App\Contracts\VictoryGames\BrowserSessionManager;
use App\Services\VictoryGames\PlaywrightBrowserSessionManager;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(BrowserSessionManager::class, PlaywrightBrowserSessionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
