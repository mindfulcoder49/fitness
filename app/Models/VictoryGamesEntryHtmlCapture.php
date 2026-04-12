<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VictoryGamesEntryHtmlCapture extends Model
{
    protected $table = 'victory_games_entry_html_captures';

    protected $fillable = [
        'entry_id',
        'step_number',
        'url',
        'html',
    ];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesEntry::class, 'entry_id');
    }
}
