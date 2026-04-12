<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VictoryGamesEntryMemory extends Model
{
    protected $table = 'victory_games_entry_memory';

    protected $fillable = [
        'entry_id',
        'memory_key',
        'memory_value',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesEntry::class, 'entry_id');
    }
}
