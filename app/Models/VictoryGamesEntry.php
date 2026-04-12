<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VictoryGamesEntry extends Model
{
    protected $table = 'victory_games_entries';

    protected $fillable = [
        'competition_id', 'app_id', 'victor_id', 'started_by_user_id', 'external_entry_id', 'external_user_id',
        'run_origin',
        'app_url', 'app_goal', 'app_mode', 'session_provider', 'session_model',
        'session_status', 'session_external_id', 'run_config', 'end_reason', 'submission_note', 'placement',
        'entry_profile', 'postmortem_run_analysis', 'postmortem_html_analysis',
        'postmortem_recommendations', 'submitted_at', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'entry_profile' => 'array',
        'run_config' => 'array',
        'submitted_at'  => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesCompetition::class, 'competition_id');
    }

    public function victor(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesVictor::class, 'victor_id');
    }

    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by_user_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesApp::class, 'app_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(VictoryGamesRunStep::class, 'entry_id')->orderBy('step_number');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(VictoryGamesEntryLog::class, 'entry_id')->orderBy('id');
    }

    public function htmlCaptures(): HasMany
    {
        return $this->hasMany(VictoryGamesEntryHtmlCapture::class, 'entry_id')->orderBy('step_number');
    }

    public function memoryItems(): HasMany
    {
        return $this->hasMany(VictoryGamesEntryMemory::class, 'entry_id')->orderBy('memory_key');
    }

    public function placementLabel(): ?string
    {
        return match ($this->placement) {
            1 => '1st',
            2 => '2nd',
            3 => '3rd',
            default => null,
        };
    }

    public function isOwnedByUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return $this->started_by_user_id === $user->id
            || $this->victor?->user_id === $user->id;
    }

    public function isActiveNativeRun(): bool
    {
        return $this->run_origin === 'native'
            && in_array($this->session_status, ['queued', 'running', 'analyzing'], true);
    }

    public function canBeDeletedBy(?User $user): bool
    {
        if (!$user || $this->isActiveNativeRun()) {
            return false;
        }

        return $user->is_admin || $this->isOwnedByUser($user);
    }

    public function reusableConfig(array $defaults = []): array
    {
        return [
            'goal' => (string) ($this->app_goal ?: ($defaults['goal'] ?? '')),
            'start_url' => (string) ($this->app_url ?: ($defaults['start_url'] ?? '')),
            'mode' => (string) ($this->app_mode ?: ($defaults['mode'] ?? 'desktop')),
            'provider' => (string) ($this->session_provider ?: ($defaults['provider'] ?? '')),
            'model' => (string) ($this->session_model ?: ($defaults['model'] ?? '')),
            'max_steps' => max(1, (int) data_get($this->run_config, 'max_steps', $defaults['max_steps'] ?? 1)),
        ];
    }

    /** Human-readable app hostname for display. */
    public function appHostname(): string
    {
        if (!$this->app_url) return 'Unknown';
        $host = parse_url($this->app_url, PHP_URL_HOST) ?: $this->app_url;
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
