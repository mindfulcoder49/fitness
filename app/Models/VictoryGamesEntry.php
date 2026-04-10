<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VictoryGamesEntry extends Model
{
    protected $table = 'victory_games_entries';

    protected $fillable = [
        'competition_id', 'app_id', 'victor_id', 'external_entry_id', 'external_user_id',
        'app_url', 'app_goal', 'app_mode', 'session_provider', 'session_model',
        'session_status', 'session_external_id', 'submission_note', 'placement',
        'entry_profile', 'postmortem_run_analysis', 'postmortem_html_analysis',
        'postmortem_recommendations', 'submitted_at',
    ];

    protected $casts = [
        'entry_profile' => 'array',
        'submitted_at'  => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesCompetition::class, 'competition_id');
    }

    public function victor(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesVictor::class, 'victor_id');
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesApp::class, 'app_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(VictoryGamesRunStep::class, 'entry_id')->orderBy('step_number');
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

    /** Human-readable app hostname for display. */
    public function appHostname(): string
    {
        if (!$this->app_url) return 'Unknown';
        $host = parse_url($this->app_url, PHP_URL_HOST) ?: $this->app_url;
        return str_starts_with($host, 'www.') ? substr($host, 4) : $host;
    }
}
