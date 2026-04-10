<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VictoryGamesCertificate extends Model
{
    protected $table = 'victory_games_certificates';

    protected $fillable = [
        'victor_id', 'competition_id', 'certificate_type', 'download_token', 'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function victor(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesVictor::class, 'victor_id');
    }

    public function competition(): BelongsTo
    {
        return $this->belongsTo(VictoryGamesCompetition::class, 'competition_id');
    }

    public function typeLabel(): string
    {
        return match ($this->certificate_type) {
            '1st'           => '1st Place',
            '2nd'           => '2nd Place',
            '3rd'           => '3rd Place',
            'participation' => 'Participation',
            default         => ucfirst($this->certificate_type),
        };
    }

    /** Generate a secure unique download token. */
    public static function generateToken(): string
    {
        return Str::random(64);
    }
}
