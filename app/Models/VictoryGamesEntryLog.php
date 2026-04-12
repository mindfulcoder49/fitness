<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VictoryGamesEntryLog extends Model
{
    protected $table = 'victory_games_entry_logs';

    protected $fillable = [
        'entry_id',
        'step_number',
        'level',
        'message',
        'details',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesEntry::class, 'entry_id');
    }
}
