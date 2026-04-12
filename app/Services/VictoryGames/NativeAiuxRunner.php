<?php

namespace App\Services\VictoryGames;

use App\Ai\Agents\VictoryGames\HtmlPostmortemAgent;
use App\Ai\Agents\VictoryGames\NativeRunPlannerAgent;
use App\Ai\Agents\VictoryGames\RunPostmortemAgent;
use App\Contracts\VictoryGames\BrowserSession;
use App\Contracts\VictoryGames\BrowserSessionManager;
use App\Models\VictoryGamesEntry;
use App\Models\VictoryGamesEntryHtmlCapture;
use App\Models\VictoryGamesEntryLog;
use App\Models\VictoryGamesEntryMemory;
use App\Models\VictoryGamesRunStep;
use App\Support\VictoryGames\NativeAiuxHtmlSanitizer;
use App\Support\VictoryGames\NativeAiuxLoopDetector;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class NativeAiuxRunner
{
    public function __construct(
        private readonly BrowserSessionManager $browserSessions,
        private readonly NativeAiuxHtmlSanitizer $htmlSanitizer,
        private readonly NativeAiuxLoopDetector $loopDetector,
    ) {}

    public function run(VictoryGamesEntry $entry): void
    {
        $entry = $entry->fresh();

        if (!$entry) {
            return;
        }

        if ($entry->session_status === 'stopped') {
            $entry->forceFill([
                'end_reason' => $entry->end_reason ?: 'Stopped before the queued run started.',
                'completed_at' => $entry->completed_at ?: now(),
            ])->save();

            return;
        }

        $entry->forceFill([
            'session_status' => 'running',
            'started_at' => $entry->started_at ?: now(),
            'completed_at' => null,
            'end_reason' => null,
        ])->save();

        $this->log($entry, 'info', 'Native AIUX run started.');

        $browser = null;
        $state = [
            'status' => 'running',
            'end_reason' => null,
            'current_step' => 0,
            'current_url' => $entry->app_url,
            'current_html' => '',
            'memory' => $entry->memoryItems()->pluck('memory_value', 'memory_key')->all(),
            'action_history' => [],
            'recent_action_fingerprints' => [],
        ];

        try {
            $startUrl = trim((string) $entry->app_url);

            if ($startUrl === '') {
                throw new \RuntimeException('A start URL is required to run a native AIUX test.');
            }

            $browser = $this->browserSessions->open($entry->app_mode ?: config('victory_games.native_runs.default_mode', 'desktop'));
            $browser->navigate($startUrl);

            $initialCapture = $this->captureStep(
                $entry,
                $browser,
                stepNumber: 0,
                actionType: 'initialize',
                actionParams: [],
                intent: 'Open the requested start URL and capture the initial page state.',
                reasoning: 'Initialize the browser session before planning the next action.',
                actionResult: 'Browser session initialized.',
                success: true,
                errorMessage: null,
            );

            $state['current_url'] = $initialCapture['url'];
            $state['current_html'] = $initialCapture['agent_html'];
            $state['current_step'] = 0;

            while ($state['status'] === 'running') {
                $entry->refresh();

                if ($entry->session_status === 'stopped') {
                    $state['status'] = 'stopped';
                    $state['end_reason'] = $entry->end_reason ?: 'Stopped by user.';
                    $this->log($entry, 'warning', 'Run stop requested by user.', stepNumber: $state['current_step']);
                    break;
                }

                if ($state['current_step'] >= $this->maxSteps($entry)) {
                    $state['status'] = 'failed';
                    $state['end_reason'] = 'Max steps reached.';
                    $this->log($entry, 'warning', 'Run hit the max-step limit.', stepNumber: $state['current_step']);
                    break;
                }

                $decision = $this->planNextAction($entry, $state);

                if (!empty($decision['last_action_result']) && !empty($state['action_history'])) {
                    $state['action_history'][array_key_last($state['action_history'])]['action_outcome'] = $decision['last_action_result'];
                }

                $execution = $this->executeAction($browser, $entry, $state, $decision);
                $nextStep = $state['current_step'] + 1;

                if (($execution['action_type'] ?? null) === 'save_to_memory') {
                    $this->persistMemory(
                        $entry,
                        (string) Arr::get($execution, 'action_params.key', ''),
                        (string) Arr::get($execution, 'action_params.value', '')
                    );
                    $state['memory'] = $entry->memoryItems()->pluck('memory_value', 'memory_key')->all();
                }

                if (in_array($execution['action_type'], ['finish', 'fail', 'give_up'], true)) {
                    $this->recordTerminalStep(
                        $entry,
                        stepNumber: $nextStep,
                        actionType: $execution['action_type'],
                        actionParams: $execution['action_params'],
                        intent: $execution['intent'],
                        reasoning: $execution['reasoning'],
                        actionResult: $execution['action_result'],
                        success: $execution['success'],
                        errorMessage: $execution['error_message'],
                        pageUrl: $state['current_url'],
                    );

                    $state['current_step'] = $nextStep;
                    $state['status'] = $execution['terminal_status'] ?? 'failed';
                    $state['end_reason'] = $execution['terminal_reason'] ?? 'Run ended.';
                    break;
                }

                $capture = $this->captureStep(
                    $entry,
                    $browser,
                    stepNumber: $nextStep,
                    actionType: $execution['action_type'],
                    actionParams: $execution['action_params'],
                    intent: $execution['intent'],
                    reasoning: $execution['reasoning'],
                    actionResult: $execution['action_result'],
                    success: $execution['success'],
                    errorMessage: $execution['error_message'],
                );

                $state['current_step'] = $nextStep;
                $state['current_url'] = $capture['url'];
                $state['current_html'] = $capture['agent_html'];
                $state['action_history'][] = [
                    'step' => $nextStep,
                    'action_type' => $execution['action_type'],
                    'action_params' => $execution['action_params'],
                    'intent' => $execution['intent'],
                    'reasoning' => $execution['reasoning'],
                    'execution_result' => $execution['action_result'],
                    'action_outcome' => null,
                    'url' => $capture['url'],
                    'success' => $execution['success'],
                    'error' => $execution['error_message'],
                ];

                $state['recent_action_fingerprints'][] = $this->loopDetector->fingerprint(
                    $execution['action_type'],
                    $execution['action_params']
                );
                $state['recent_action_fingerprints'] = array_slice(
                    $state['recent_action_fingerprints'],
                    -$this->loopDetectionWindow($entry)
                );

                if ($this->loopDetector->isLooping(
                    $state['recent_action_fingerprints'],
                    config('victory_games.native_runs.loop_detection_rules', []),
                    $state['action_history']
                )) {
                    $state['status'] = 'loop_detected';
                    $state['end_reason'] = 'Detected repeated low-progress actions on the same page.';
                    $this->log($entry, 'warning', 'Loop detection triggered.', $state['end_reason'], $state['current_step']);

                    $this->recordTerminalStep(
                        $entry,
                        stepNumber: $state['current_step'] + 1,
                        actionType: 'give_up',
                        actionParams: ['reason' => $state['end_reason']],
                        intent: 'Stop the run because the agent is looping.',
                        reasoning: 'The same low-progress actions repeated on the same page.',
                        actionResult: $state['end_reason'],
                        success: false,
                        errorMessage: $state['end_reason'],
                        pageUrl: $state['current_url'],
                    );

                    $state['current_step']++;
                    break;
                }
            }
        } catch (\Throwable $exception) {
            $state['status'] = 'failed';
            $state['end_reason'] = $exception->getMessage();
            $this->log($entry, 'error', 'Native AIUX run failed unexpectedly.', $exception->getMessage(), $state['current_step'] ?: null);
        } finally {
            if ($browser instanceof BrowserSession) {
                try {
                    $browser->close();
                } catch (\Throwable) {
                }
            }
        }

        $this->finalize($entry->fresh(), $state);
    }

    private function finalize(?VictoryGamesEntry $entry, array $state): void
    {
        if (!$entry) {
            return;
        }

        $finalStatus = $state['status'] ?? 'failed';
        $endReason = $state['end_reason'] ?? 'Run ended unexpectedly.';

        if ($finalStatus === 'stopped') {
            $entry->forceFill([
                'session_status' => 'stopped',
                'end_reason' => $endReason,
                'completed_at' => now(),
            ])->save();

            return;
        }

        $entry->forceFill([
            'session_status' => 'analyzing',
            'end_reason' => $endReason,
        ])->save();

        $this->runPostmortem($entry, $state);

        $entry->forceFill([
            'session_status' => $finalStatus,
            'end_reason' => $endReason,
            'completed_at' => now(),
        ])->save();
    }

    private function runPostmortem(VictoryGamesEntry $entry, array $state): void
    {
        $this->log($entry, 'info', 'Run postmortem started.', stepNumber: $state['current_step'] ?? null);

        try {
            $runFacts = [
                'goal' => $entry->app_goal,
                'final_status' => $state['status'] ?? $entry->session_status,
                'end_reason' => $state['end_reason'] ?? $entry->end_reason,
                'total_actions' => count($state['action_history'] ?? []),
                'unique_urls_visited' => array_values(array_unique(array_filter(array_map(
                    fn (array $action) => $action['url'] ?? null,
                    $state['action_history'] ?? []
                )))),
                'action_history' => $state['action_history'] ?? [],
                'memory' => $state['memory'] ?? [],
                'logs' => $entry->logs()->latest('id')->limit(20)->get(['level', 'message', 'details'])->values(),
            ];

            $provider = $entry->session_provider ?: config('ai.default');
            $runPrompt = json_encode($runFacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $runResponse = $this->promptWithTelemetry(
                $entry,
                phase: 'run_postmortem',
                stepNumber: $state['current_step'] ?? null,
                prompt: $runPrompt,
                context: [
                    'provider' => $provider,
                    'model' => $entry->session_model,
                    'action_history_count' => count($state['action_history'] ?? []),
                    'memory_count' => count($state['memory'] ?? []),
                ],
                callback: fn () => RunPostmortemAgent::make()->prompt(
                    prompt: $runPrompt,
                    provider: $provider,
                    model: $entry->session_model,
                    timeout: config('victory_games.native_runs.postmortem_timeout', 120),
                ),
            );

            $pages = $entry->htmlCaptures()
                ->orderBy('step_number')
                ->get()
                ->groupBy('url')
                ->map(fn ($captures) => $captures->last())
                ->values()
                ->take((int) config('victory_games.native_runs.postmortem_page_limit', 10))
                ->map(fn (VictoryGamesEntryHtmlCapture $capture) => [
                    'step_number' => $capture->step_number,
                    'url' => $capture->url,
                    'html' => $capture->html,
                ])
                ->all();

            $htmlPrompt = json_encode([
                'goal' => $entry->app_goal,
                'pages' => $pages,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            $htmlResponse = $this->promptWithTelemetry(
                $entry,
                phase: 'html_postmortem',
                stepNumber: $state['current_step'] ?? null,
                prompt: $htmlPrompt,
                context: [
                    'provider' => $provider,
                    'model' => $entry->session_model,
                    'pages_count' => count($pages),
                ],
                callback: fn () => HtmlPostmortemAgent::make()->prompt(
                    prompt: $htmlPrompt,
                    provider: $provider,
                    model: $entry->session_model,
                    timeout: config('victory_games.native_runs.postmortem_timeout', 120),
                ),
            );

            $entry->forceFill([
                'postmortem_run_analysis' => (string) ($runResponse['run_analysis'] ?? ''),
                'postmortem_html_analysis' => (string) ($htmlResponse['html_analysis'] ?? ''),
                'postmortem_recommendations' => (string) ($htmlResponse['recommendations'] ?? $runResponse['recommendations'] ?? ''),
            ])->save();

            $this->log($entry, 'info', 'Run postmortem completed.', stepNumber: $state['current_step'] ?? null);
        } catch (\Throwable $exception) {
            $entry->forceFill($this->heuristicPostmortem($entry, $state))->save();
            $this->log($entry, 'warning', 'Run postmortem fell back to heuristic output.', $exception->getMessage(), $state['current_step'] ?? null);
        }
    }

    private function heuristicPostmortem(VictoryGamesEntry $entry, array $state): array
    {
        $actions = $state['action_history'] ?? [];
        $uniqueUrls = array_values(array_unique(array_filter(array_map(fn (array $action) => $action['url'] ?? null, $actions))));

        $runAnalysis = implode("\n", [
            'Goal: '.($entry->app_goal ?: 'Unknown goal'),
            'Status: '.($state['status'] ?? $entry->session_status).' ('.($state['end_reason'] ?? $entry->end_reason).')',
            'Actions executed: '.count($actions),
            'Unique URLs visited: '.count($uniqueUrls),
            'Note: This report used the heuristic fallback because the AI postmortem call was unavailable.',
        ]);

        $pages = $entry->htmlCaptures()->orderBy('step_number')->limit(5)->get();
        $htmlSummary = $pages->map(function (VictoryGamesEntryHtmlCapture $capture) {
            $html = strtolower($capture->html);

            return sprintf(
                '%s — header:%s nav:%s main:%s html_len:%d',
                $capture->url ?: 'unknown-url',
                str_contains($html, '<header') ? 'yes' : 'no',
                str_contains($html, '<nav') ? 'yes' : 'no',
                str_contains($html, '<main') ? 'yes' : 'no',
                strlen($capture->html)
            );
        })->implode("\n");

        return [
            'postmortem_run_analysis' => $runAnalysis,
            'postmortem_html_analysis' => $htmlSummary !== ''
                ? $htmlSummary
                : 'No HTML captures were available for postmortem analysis.',
            'postmortem_recommendations' => implode("\n", [
                '1. Reduce repeated same-page inspections before giving up.',
                '2. Add clearer success criteria for the target goal.',
                '3. Review the captured HTML on failed steps for missing landmarks or dead-end flows.',
            ]),
        ];
    }

    private function planNextAction(VictoryGamesEntry $entry, array $state): array
    {
        $prompt = implode("\n\n", [
            'Goal: '.$entry->app_goal,
            'Mode: '.($entry->app_mode ?: config('victory_games.native_runs.default_mode', 'desktop')),
            'Current URL: '.($state['current_url'] ?: 'unknown'),
            'Current step: '.(string) $state['current_step'],
            'Memory: '.json_encode($state['memory'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'Recent action history:',
            $this->formatHistory($state['action_history']),
            'Current page HTML:',
            $state['current_html'],
        ]);

        $provider = $entry->session_provider ?: config('ai.default');

        $response = $this->promptWithTelemetry(
            $entry,
            phase: 'planner',
            stepNumber: $state['current_step'],
            prompt: $prompt,
            context: [
                'provider' => $provider,
                'model' => $entry->session_model,
                'current_url' => $state['current_url'] ?: 'unknown',
                'action_history_count' => count($state['action_history'] ?? []),
                'memory_count' => count($state['memory'] ?? []),
                'current_html_bytes' => strlen((string) ($state['current_html'] ?? '')),
            ],
            callback: fn () => NativeRunPlannerAgent::make()->prompt(
                prompt: $prompt,
                provider: $provider,
                model: $entry->session_model,
                timeout: config('victory_games.native_runs.planner_timeout', 120),
            ),
        );

        return $this->normalizePlannerDecision($response->toArray());
    }

    private function promptWithTelemetry(
        VictoryGamesEntry $entry,
        string $phase,
        ?int $stepNumber,
        string $prompt,
        array $context,
        callable $callback,
    ): mixed {
        $startedAt = microtime(true);

        $this->log(
            $entry,
            'debug',
            $this->phaseLabel($phase).' prompt started.',
            $this->encodeLogDetails(array_merge(
                ['phase' => $phase],
                $context,
                $this->runtimeSnapshot($prompt, 'start_'),
            )),
            $stepNumber,
        );

        try {
            $result = $callback();

            $this->log(
                $entry,
                'debug',
                $this->phaseLabel($phase).' prompt completed.',
                $this->encodeLogDetails(array_merge(
                    ['phase' => $phase],
                    $context,
                    ['elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000)],
                    $this->runtimeSnapshot(null, 'end_'),
                )),
                $stepNumber,
            );

            return $result;
        } catch (\Throwable $exception) {
            $this->log(
                $entry,
                'warning',
                $this->phaseLabel($phase).' prompt failed.',
                $this->encodeLogDetails(array_merge(
                    ['phase' => $phase],
                    $context,
                    [
                        'elapsed_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                        'exception_class' => $exception::class,
                        'exception_message' => $exception->getMessage(),
                    ],
                    $this->runtimeSnapshot(null, 'end_'),
                )),
                $stepNumber,
            );

            throw $exception;
        }
    }

    private function normalizePlannerDecision(array $decision): array
    {
        $action = (string) ($decision['action'] ?? 'fail');
        $params = [
            'url' => $decision['url'] ?? null,
            'script' => $decision['script'] ?? null,
            'summary' => $decision['summary'] ?? null,
            'reason' => $decision['reason'] ?? null,
            'key' => $decision['key'] ?? null,
            'value' => $decision['value'] ?? null,
        ];

        return match ($action) {
            'navigate' => [
                'action' => 'navigate',
                'params' => ['url' => (string) ($params['url'] ?? '')],
                'intent' => (string) ($decision['intent'] ?? 'Navigate to a new page.'),
                'reasoning' => (string) ($decision['reasoning'] ?? ''),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
            'execute_js' => [
                'action' => 'execute_js',
                'params' => ['script' => (string) ($params['script'] ?? '')],
                'intent' => (string) ($decision['intent'] ?? 'Inspect or interact with the page using JavaScript.'),
                'reasoning' => (string) ($decision['reasoning'] ?? ''),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
            'save_to_memory' => [
                'action' => 'save_to_memory',
                'params' => [
                    'key' => (string) ($params['key'] ?? ''),
                    'value' => (string) ($params['value'] ?? ''),
                ],
                'intent' => (string) ($decision['intent'] ?? 'Store a fact for later steps.'),
                'reasoning' => (string) ($decision['reasoning'] ?? ''),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
            'finish' => [
                'action' => 'finish',
                'params' => ['summary' => (string) ($params['summary'] ?? 'The goal appears complete.')],
                'intent' => (string) ($decision['intent'] ?? 'Finish the run.'),
                'reasoning' => (string) ($decision['reasoning'] ?? ''),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
            'give_up' => [
                'action' => 'give_up',
                'params' => ['reason' => (string) ($params['reason'] ?? 'The run is stuck without meaningful progress.')],
                'intent' => (string) ($decision['intent'] ?? 'Stop the run because meaningful progress is no longer happening.'),
                'reasoning' => (string) ($decision['reasoning'] ?? ''),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
            default => [
                'action' => 'fail',
                'params' => ['reason' => (string) ($params['reason'] ?? 'The planner returned an invalid action.')],
                'intent' => (string) ($decision['intent'] ?? 'Stop the run safely.'),
                'reasoning' => (string) ($decision['reasoning'] ?? 'The planner response was invalid.'),
                'last_action_result' => $decision['last_action_result'] ?? null,
            ],
        };
    }

    private function executeAction(BrowserSession $browser, VictoryGamesEntry $entry, array $state, array $decision): array
    {
        $action = $decision['action'];
        $params = $decision['params'];
        $intent = (string) ($decision['intent'] ?? '');
        $reasoning = (string) ($decision['reasoning'] ?? '');

        return match ($action) {
            'navigate' => $this->executeNavigate($browser, $params, $intent, $reasoning),
            'execute_js' => $this->executeJavaScript($browser, $params, $intent, $reasoning),
            'save_to_memory' => [
                'action_type' => 'save_to_memory',
                'action_params' => $params,
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => 'Saved fact to agent memory.',
                'success' => true,
                'error_message' => null,
            ],
            'finish' => [
                'action_type' => 'finish',
                'action_params' => $params,
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => (string) ($params['summary'] ?? 'The goal appears complete.'),
                'success' => true,
                'error_message' => null,
                'terminal_status' => 'completed',
                'terminal_reason' => (string) ($params['summary'] ?? 'The goal appears complete.'),
            ],
            'give_up' => [
                'action_type' => 'give_up',
                'action_params' => $params,
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => (string) ($params['reason'] ?? 'The run is stuck.'),
                'success' => false,
                'error_message' => (string) ($params['reason'] ?? 'The run is stuck.'),
                'terminal_status' => 'loop_detected',
                'terminal_reason' => (string) ($params['reason'] ?? 'The run is stuck.'),
            ],
            default => [
                'action_type' => 'fail',
                'action_params' => $params,
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => (string) ($params['reason'] ?? 'The planner returned an invalid action.'),
                'success' => false,
                'error_message' => (string) ($params['reason'] ?? 'The planner returned an invalid action.'),
                'terminal_status' => 'failed',
                'terminal_reason' => (string) ($params['reason'] ?? 'The planner returned an invalid action.'),
            ],
        };
    }

    private function executeNavigate(BrowserSession $browser, array $params, string $intent, string $reasoning): array
    {
        $url = trim((string) ($params['url'] ?? ''));

        if ($url === '') {
            return [
                'action_type' => 'fail',
                'action_params' => ['reason' => 'navigate requires a URL.'],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => 'navigate requires a URL.',
                'success' => false,
                'error_message' => 'navigate requires a URL.',
                'terminal_status' => 'failed',
                'terminal_reason' => 'navigate requires a URL.',
            ];
        }

        try {
            $browser->navigate($url);

            return [
                'action_type' => 'navigate',
                'action_params' => ['url' => $url],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => 'Navigated to '.$url,
                'success' => true,
                'error_message' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'action_type' => 'navigate',
                'action_params' => ['url' => $url],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => null,
                'success' => false,
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    private function executeJavaScript(BrowserSession $browser, array $params, string $intent, string $reasoning): array
    {
        $script = (string) ($params['script'] ?? '');

        if (trim($script) === '') {
            return [
                'action_type' => 'fail',
                'action_params' => ['reason' => 'execute_js requires a script.'],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => 'execute_js requires a script.',
                'success' => false,
                'error_message' => 'execute_js requires a script.',
                'terminal_status' => 'failed',
                'terminal_reason' => 'execute_js requires a script.',
            ];
        }

        try {
            $result = $browser->executeJavaScript($script);

            return [
                'action_type' => 'execute_js',
                'action_params' => ['script' => $script],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => is_string($result) || $result === null ? $result : json_encode($result),
                'success' => true,
                'error_message' => null,
            ];
        } catch (\Throwable $exception) {
            return [
                'action_type' => 'execute_js',
                'action_params' => ['script' => $script],
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => null,
                'success' => false,
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    private function captureStep(
        VictoryGamesEntry $entry,
        BrowserSession $browser,
        int $stepNumber,
        string $actionType,
        array $actionParams,
        string $intent,
        string $reasoning,
        mixed $actionResult,
        bool $success,
        ?string $errorMessage,
    ): array {
        $screenshotRelativePath = "victory-games/screenshots/{$entry->id}/step_{$stepNumber}.png";
        $screenshotAbsolutePath = Storage::disk('public')->path($screenshotRelativePath);
        $directory = dirname($screenshotAbsolutePath);

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $browser->screenshot($screenshotAbsolutePath);
        $url = $browser->url();
        $rawHtml = $browser->html();

        $agentHtml = $this->htmlSanitizer->truncate(
            $this->htmlSanitizer->sanitize($rawHtml, 'agent'),
            (int) config('victory_games.native_runs.html_character_limit', 40000)
        );

        $postmortemHtml = $this->htmlSanitizer->sanitize($rawHtml, 'postmortem');

        DB::transaction(function () use (
            $entry,
            $stepNumber,
            $actionType,
            $actionParams,
            $intent,
            $reasoning,
            $actionResult,
            $success,
            $errorMessage,
            $url,
            $screenshotRelativePath,
            $postmortemHtml,
        ): void {
            VictoryGamesRunStep::create([
                'entry_id' => $entry->id,
                'step_number' => $stepNumber,
                'action_type' => $actionType,
                'action_params' => $actionParams,
                'intent' => $intent,
                'reasoning' => $reasoning,
                'action_result' => is_string($actionResult) || $actionResult === null ? $actionResult : json_encode($actionResult),
                'success' => $success,
                'error_message' => $errorMessage,
                'page_url' => $url,
                'screenshot_path' => $screenshotRelativePath,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);

            VictoryGamesEntryHtmlCapture::create([
                'entry_id' => $entry->id,
                'step_number' => $stepNumber,
                'url' => $url,
                'html' => $postmortemHtml,
            ]);
        });

        $this->log(
            $entry,
            $success ? 'info' : 'warning',
            'Captured run state after action '.$actionType.'.',
            $errorMessage,
            $stepNumber,
        );

        return [
            'url' => $url,
            'agent_html' => $agentHtml,
        ];
    }

    private function recordTerminalStep(
        VictoryGamesEntry $entry,
        int $stepNumber,
        string $actionType,
        array $actionParams,
        string $intent,
        string $reasoning,
        mixed $actionResult,
        bool $success,
        ?string $errorMessage,
        ?string $pageUrl,
    ): void {
        VictoryGamesRunStep::create([
            'entry_id' => $entry->id,
            'step_number' => $stepNumber,
            'action_type' => $actionType,
            'action_params' => $actionParams,
            'intent' => $intent,
            'reasoning' => $reasoning,
            'action_result' => is_string($actionResult) || $actionResult === null ? $actionResult : json_encode($actionResult),
            'success' => $success,
            'error_message' => $errorMessage,
            'page_url' => $pageUrl,
            'screenshot_path' => null,
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);
    }

    private function persistMemory(VictoryGamesEntry $entry, string $key, string $value): void
    {
        if ($key === '' || $value === '') {
            return;
        }

        VictoryGamesEntryMemory::updateOrCreate(
            [
                'entry_id' => $entry->id,
                'memory_key' => $key,
            ],
            [
                'memory_value' => $value,
            ],
        );
    }

    private function formatHistory(array $actionHistory): string
    {
        if (empty($actionHistory)) {
            return 'No actions yet.';
        }

        $recent = array_slice($actionHistory, -5);

        return implode("\n", array_map(function (array $item) {
            $parts = [
                ($item['step'] ?? '?').'.',
                'url='.(string) ($item['url'] ?? 'unknown'),
                'action='.(string) ($item['action_type'] ?? 'unknown'),
                'success='.(($item['success'] ?? false) ? 'true' : 'false'),
            ];

            if (!empty($item['action_outcome'])) {
                $parts[] = 'outcome='.(string) $item['action_outcome'];
            }

            if (!empty($item['execution_result'])) {
                $parts[] = 'result='.(string) $item['execution_result'];
            }

            return implode(' ', $parts);
        }, $recent));
    }

    private function log(
        VictoryGamesEntry $entry,
        string $level,
        string $message,
        ?string $details = null,
        ?int $stepNumber = null,
    ): void {
        VictoryGamesEntryLog::create([
            'entry_id' => $entry->id,
            'step_number' => $stepNumber,
            'level' => $level,
            'message' => $message,
            'details' => $details,
        ]);
    }

    private function encodeLogDetails(array $details): ?string
    {
        $payload = array_filter($details, fn (mixed $value): bool => $value !== null);

        if ($payload === []) {
            return null;
        }

        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: null;
    }

    private function runtimeSnapshot(?string $prompt = null, string $prefix = ''): array
    {
        $details = [
            $prefix.'fd_count' => $this->openFileDescriptorCount(),
            $prefix.'memory_bytes' => memory_get_usage(true),
            $prefix.'memory_mb' => round(memory_get_usage(true) / 1048576, 2),
            $prefix.'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
        ];

        if ($prompt !== null) {
            $details[$prefix.'prompt_bytes'] = strlen($prompt);
            $details[$prefix.'prompt_lines'] = substr_count($prompt, "\n") + 1;
        }

        return $details;
    }

    private function openFileDescriptorCount(): ?int
    {
        $descriptors = glob('/proc/self/fd/*');

        return is_array($descriptors) ? count($descriptors) : null;
    }

    private function phaseLabel(string $phase): string
    {
        return match ($phase) {
            'planner' => 'Planner agent',
            'run_postmortem' => 'Run postmortem agent',
            'html_postmortem' => 'HTML postmortem agent',
            default => ucfirst(str_replace('_', ' ', $phase)),
        };
    }

    private function maxSteps(VictoryGamesEntry $entry): int
    {
        $minSteps = max(1, (int) config('victory_games.native_runs.min_steps', 1));
        $maxStepsLimit = max($minSteps, (int) config('victory_games.native_runs.max_steps_limit', 50));
        $configuredSteps = (int) Arr::get($entry->run_config, 'max_steps', config('victory_games.native_runs.max_steps', 8));

        return max($minSteps, min($maxStepsLimit, $configuredSteps));
    }

    private function loopDetectionWindow(VictoryGamesEntry $entry): int
    {
        return max(
            1,
            (int) Arr::get($entry->run_config, 'loop_detection_window', config('victory_games.native_runs.loop_detection_window', 10))
        );
    }
}
