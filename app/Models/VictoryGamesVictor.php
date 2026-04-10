<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class VictoryGamesVictor extends Model
{
    protected $table = 'victory_games_victors';

    protected $fillable = [
        'user_id', 'external_user_id', 'slug', 'display_name',
        'bio', 'avatar_path', 'github_url', 'website_url', 'twitter_url',
    ];

    protected $appends = ['avatar_url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(VictoryGamesEntry::class, 'victor_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(VictoryGamesCertificate::class, 'victor_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? Storage::url($this->avatar_path) : null;
    }

    /** Whether the given user owns this profile and can edit it. */
    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->user_id === $user->id;
    }
}
