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
- You are controlling a live browser page after the site's JavaScript has already run.
- The supplied HTML is a snapshot of the current live DOM, not a static source dump.
- Use the provided HTML, not imagined UI.
- The execute_js action runs inside the real page context. Use it to inspect DOM state, click buttons, dispatch events, read window/localStorage/sessionStorage, and verify whether the page changed.
- Prefer execute_js probes that return concise JSON-serializable results.
- Each execute_js step should do one focused thing: inspect state, click one control, choose one answer, submit one form, or read one result.
- Do not write long looping scripts, repeated click loops, long polling waits, or "complete the whole flow" scripts. Use multiple short steps instead.
- After an interaction, return the most useful evidence you can in JSON: current question, visible option labels, progress text, button labels, feedback text, URL changes, or whether the DOM changed.
- If a prior step did not change the question, progress, URL, or visible state, do not repeat the same tactic. Pick a more targeted probe or conclude the run is blocked.
- Use navigate only when changing pages is the best next step.
- If the goal involves clicking, selecting, submitting, or progressing through a multi-step UI, do not fail or give_up from the initial page state without first attempting at least one targeted execute_js probe or interaction.
- Do not claim that you only have static HTML or that JavaScript interactions are impossible unless a targeted execute_js probe proves the relevant workflow cannot progress.
- For quizzes, forms, and multi-step flows, use execute_js to find the next control, trigger it, and confirm whether the DOM, URL, question text, progress indicator, or visible state changed.
- Use action history and returned feedback to infer the next answer instead of retrying blind clicks.
- Use save_to_memory when you learn a durable fact that will help later steps, such as a discovered answer pattern or workflow rule.
- Respect the remaining step budget. When few steps remain, avoid speculative retries and make the highest-signal move.
- If you are repeating the same inspection without learning anything new, finish or give_up.
- If the goal is satisfied, use finish with a short findings report.
- If the run is blocked by a real issue, use fail or give_up with a specific reason.
- Keep reasoning concrete and brief.
- Always return every schema field.
- Set unused action-specific fields to null.
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
            'url' => $schema->string()->nullable()->required(),
            'script' => $schema->string()->nullable()->required(),
            'summary' => $schema->string()->nullable()->required(),
            'reason' => $schema->string()->nullable()->required(),
            'key' => $schema->string()->nullable()->required(),
            'value' => $schema->string()->nullable()->required(),
            'intent' => $schema->string()->required(),
            'reasoning' => $schema->string()->required(),
            'last_action_result' => $schema->string()->nullable()->required(),
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
