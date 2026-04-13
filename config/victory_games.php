<?php

return [

    'native_runs' => [
        'default_mode' => env('VICTORY_GAMES_NATIVE_DEFAULT_MODE', 'desktop'),
        'min_steps' => (int) env('VICTORY_GAMES_NATIVE_MIN_STEPS', 1),
        'max_steps' => (int) env('VICTORY_GAMES_NATIVE_MAX_STEPS', 8),
        'max_steps_limit' => (int) env('VICTORY_GAMES_NATIVE_MAX_STEPS_LIMIT', 50),
        'html_character_limit' => (int) env('VICTORY_GAMES_NATIVE_HTML_CHARACTER_LIMIT', 40000),
        'postmortem_page_limit' => (int) env('VICTORY_GAMES_NATIVE_POSTMORTEM_PAGE_LIMIT', 10),
        'poll_interval_seconds' => (int) env('VICTORY_GAMES_NATIVE_POLL_INTERVAL_SECONDS', 4),
        'planner_timeout' => (int) env('VICTORY_GAMES_NATIVE_PLANNER_TIMEOUT', 120),
        'postmortem_timeout' => (int) env('VICTORY_GAMES_NATIVE_POSTMORTEM_TIMEOUT', 120),
        'prompt_retry_attempts' => (int) env('VICTORY_GAMES_NATIVE_PROMPT_RETRY_ATTEMPTS', 5),
        'prompt_retry_backoff_ms' => (int) env('VICTORY_GAMES_NATIVE_PROMPT_RETRY_BACKOFF_MS', 500),
        'loop_detection_window' => (int) env('VICTORY_GAMES_NATIVE_LOOP_WINDOW', 10),
        'loop_detection_rules' => [
            'repeat_single' => (int) env('VICTORY_GAMES_NATIVE_LOOP_REPEAT_SINGLE', 4),
            'repeat_alternating' => (int) env('VICTORY_GAMES_NATIVE_LOOP_REPEAT_ALTERNATING', 3),
            'min_actions_before_loop' => (int) env('VICTORY_GAMES_NATIVE_LOOP_MIN_ACTIONS', 6),
            'stale_url_actions' => (int) env('VICTORY_GAMES_NATIVE_LOOP_STALE_URL_ACTIONS', 10),
        ],
        'providers' => [
            'openai' => [
                'label' => 'OpenAI',
                'enabled' => (bool) env('OPENAI_API_KEY'),
                'default_model' => env('VICTORY_GAMES_NATIVE_OPENAI_MODEL', 'gpt-5-mini'),
            ],
            'anthropic' => [
                'label' => 'Anthropic',
                'enabled' => (bool) env('ANTHROPIC_API_KEY'),
                'default_model' => env('VICTORY_GAMES_NATIVE_ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
            ],
            'gemini' => [
                'label' => 'Gemini',
                'enabled' => (bool) env('GEMINI_API_KEY'),
                'default_model' => env('VICTORY_GAMES_NATIVE_GEMINI_MODEL', 'gemini-2.5-flash'),
            ],
        ],
    ],

];
