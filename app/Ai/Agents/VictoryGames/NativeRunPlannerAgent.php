<?php

namespace App\Ai\Agents\VictoryGames;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[MaxTokens(1800)]
class NativeRunPlannerAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return <<<'TEXT'
You are an autonomous browser-testing agent for a user-owned web app.

Your goal is to make concrete progress toward the supplied test goal using only these actions:
- navigate
- execute_js
- save_to_memory
- finish
- fail
- give_up

Rules:
- Use the provided HTML, not imagined UI.
- Prefer execute_js probes that return concise JSON-serializable results.
- Use navigate only when changing pages is the best next step.
- If you are repeating the same inspection without learning anything new, finish or give_up.
- If the goal is satisfied, use finish with a short findings report.
- If the run is blocked by a real issue, use fail or give_up with a specific reason.
- Keep reasoning concrete and brief.
TEXT;
    }

    public function schema($schema): array
    {
        return [
            'action' => $schema->string()->enum([
                'navigate',
                'execute_js',
                'save_to_memory',
                'finish',
                'fail',
                'give_up',
            ])->required(),
            'params' => $schema->object(fn (JsonSchema $schema) => [
                'url' => $schema->string(),
                'script' => $schema->string(),
                'summary' => $schema->string(),
                'reason' => $schema->string(),
                'key' => $schema->string(),
                'value' => $schema->string(),
            ])->required(),
            'intent' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
            'last_action_result' => $schema->string(),
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
