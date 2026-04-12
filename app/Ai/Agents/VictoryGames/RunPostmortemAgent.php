<?php

namespace App\Ai\Agents\VictoryGames;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(2200)]
class RunPostmortemAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You analyze a browser-agent test run and return a grounded run analysis plus concrete recommendations.';
    }

    public function schema($schema): array
    {
        return [
            'run_analysis' => $schema->string()->required(),
            'recommendations' => $schema->string()->required(),
        ];
    }

    public function providerOptions(Lab|string $provider): array
    {
        $provider = $provider instanceof Lab ? $provider : Lab::tryFrom($provider);

        return match ($provider) {
            Lab::OpenAI => [
                'reasoning' => ['effort' => 'low'],
            ],
            default => [],
        };
    }
}
