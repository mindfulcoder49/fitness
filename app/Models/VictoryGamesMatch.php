<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VictoryGamesMatch extends Model
{
    protected $table = 'victory_games_matches';

    protected $fillable = [
        'run_id', 'external_match_id', 'round_number', 'match_number',
        'entry_external_ids', 'winner_entry_external_id', 'judge_reasoning', 'status',
    ];

    protected $casts = [
        'entry_external_ids' => 'array',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesRun::class, 'run_id');
    }
}
