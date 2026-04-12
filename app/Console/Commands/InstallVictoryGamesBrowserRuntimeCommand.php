<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Playwright\Node\NodeBinaryResolver;
use Symfony\Component\Process\Process;

class InstallVictoryGamesBrowserRuntimeCommand extends Command
{
    protected $signature = 'victory-games:install-browser-runtime
                            {--dry-run : Print the resolved install commands without executing them}';

    protected $description = 'Install the Playwright PHP server dependencies and Chromium runtime used for native Victory Games AIUX runs.';

    public function handle(): int
    {
        $serverDirectory = base_path('vendor/playwright-php/playwright/bin');

        if (!is_dir($serverDirectory)) {
            $this->error('Playwright PHP is not installed in vendor/.');

            return self::FAILURE;
        }

        try {
            $nodeBinary = (new NodeBinaryResolver(
                explicitPath: config('services.playwright.node_path') ?: null,
            ))->resolve();
        } catch (\Throwable $exception) {
            $this->error('Unable to resolve a Node.js 20+ binary: '.$exception->getMessage());

            return self::FAILURE;
        }

        $binDirectory = dirname($nodeBinary);
        $npmBinary = is_file($binDirectory.'/npm') ? $binDirectory.'/npm' : 'npm';
        $npxBinary = is_file($binDirectory.'/npx') ? $binDirectory.'/npx' : 'npx';

        $environment = [];

        if ($browsersPath = config('services.playwright.browsers_path')) {
            $environment['PLAYWRIGHT_BROWSERS_PATH'] = (string) $browsersPath;
        }

        $commands = [
            [$npmBinary, 'install', '--omit=dev'],
            [$npxBinary, 'playwright', 'install', 'chromium'],
        ];

        if ($this->option('dry-run')) {
            $this->line('Server directory: '.$serverDirectory);
            $this->line('Resolved node binary: '.$nodeBinary);

            foreach ($commands as $command) {
                $this->line('Would run: '.implode(' ', $command));
            }

            return self::SUCCESS;
        }

        foreach ($commands as $command) {
            $this->line('Running: '.implode(' ', $command));

            $process = new Process($command, $serverDirectory, $environment);
            $process->setTimeout(null);
            $process->setIdleTimeout(null);
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

            if (!$process->isSuccessful()) {
                $this->error('Command failed: '.implode(' ', $command));

                return self::FAILURE;
            }
        }

        $this->info('Victory Games browser runtime installed.');

        return self::SUCCESS;
    }
}
