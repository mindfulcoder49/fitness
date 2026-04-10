<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VictoryGamesRun extends Model
{
    protected $table = 'victory_games_runs';

    protected $fillable = [
        'competition_id', 'external_run_id', 'run_number', 'status',
        'champion_entry_external_id', 'pairing_strategy', 'progression_mode',
        'provider', 'model', 'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesCompetition::class, 'competition_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(VictoryGamesMatch::class, 'run_id')
            ->orderBy('round_number')
            ->orderBy('match_number');
    }
}
