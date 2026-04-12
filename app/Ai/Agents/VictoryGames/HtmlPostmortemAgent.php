<?php

namespace App\Ai\Agents\VictoryGames;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(2600)]
class HtmlPostmortemAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return 'You review captured page HTML from a browser-agent run and return structural UX/accessibility analysis plus concrete recommendations.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'html_analysis' => $schema->string()->required(),
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
