<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VictoryGamesVictor extends Model
{
    protected $table = 'victory_games_victors';

    protected $fillable = [
        'user_id', 'external_user_id', 'email', 'claim_token', 'slug', 'display_name',
        'bio', 'avatar_path', 'github_url', 'website_url', 'twitter_url',
        'welcome_email_sent_at',
    ];

    public static function generateClaimToken(): string
    {
        return Str::random(64);
    }

    protected $appends = ['avatar_url'];

    protected $casts = [
        'welcome_email_sent_at' => 'datetime',
    ];

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

    public function apps(): BelongsToMany
    {
        return $this->belongsToMany(
            VictoryGamesApp::class,
            'victory_games_app_victor',
            'victor_id',
            'app_id'
        )->withPivot('role')->withTimestamps();
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
