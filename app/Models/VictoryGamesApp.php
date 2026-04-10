<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VictoryGamesApp extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'current_url',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function victors(): BelongsToMany
    {
        return $this->belongsToMany(
            VictoryGamesVictor::class,
            'victory_games_app_victor',
            'app_id',
            'victor_id'
        )->withPivot('role')->withTimestamps();
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VictoryGamesEntry::class, 'app_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isOwnedBy(VictoryGamesVictor $victor): bool
    {
        return $this->victors()
            ->wherePivot('victor_id', $victor->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function isMember(VictoryGamesVictor $victor): bool
    {
        return $this->victors()->wherePivot('victor_id', $victor->id)->exists();
    }

    public static function generateSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'app';
        $slug = $base;
        $i    = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
