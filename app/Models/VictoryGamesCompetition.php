<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VictoryGamesCompetition extends Model
{
    protected $table = 'victory_games_competitions';

    protected $fillable = [
        'external_id', 'slug', 'name', 'description', 'status',
        'overall_narrative', 'recap_provider', 'recap_model', 'held_at',
    ];

    protected $casts = [
        'held_at' => 'datetime',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(VictoryGamesEntry::class, 'competition_id');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(VictoryGamesRun::class, 'competition_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(VictoryGamesCertificate::class, 'competition_id');
    }

    /** Entries sorted by placement (1st, 2nd, 3rd, then unplaced). */
    public function rankedEntries()
    {
        return $this->entries()
            ->orderByRaw('CASE WHEN placement IS NULL THEN 4 ELSE placement END')
            ->with('victor');
    }
}
