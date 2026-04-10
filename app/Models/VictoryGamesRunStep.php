<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VictoryGamesRunStep extends Model
{
    protected $table = 'victory_games_run_steps';

    protected $fillable = [
        'entry_id', 'step_number', 'action_type', 'action_params',
        'intent', 'reasoning', 'action_result', 'success', 'error_message',
        'page_url', 'screenshot_path', 'timestamp',
    ];

    protected $casts = [
        'action_params' => 'array',
        'success'       => 'boolean',
    ];

    protected $appends = ['screenshot_url'];

    public function entry(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesEntry::class, 'entry_id');
    }

    public function getScreenshotUrlAttribute(): ?string
    {
        return $this->screenshot_path ? Storage::url($this->screenshot_path) : null;
    }
}
