<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'is_admin',
        'can_post_images',
        'can_post_videos',
        'notifications_last_checked_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'can_post_images' => 'boolean',
        'can_post_videos' => 'boolean',
        'notifications_last_checked_at' => 'datetime',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class)->latest();
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function readChangelogs(): BelongsToMany
    {
        return $this->belongsToMany(Changelog::class, 'changelog_user')->withPivot('read_at');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class)
            ->using(GroupUser::class)
            ->withPivot('role', 'location', 'points', 'group_messages_last_checked_at')
            ->withTimestamps();
    }

    public function userAvailabilities(): HasMany
    {
        return $this->hasMany(UserAvailability::class);
    }

    public function groupMessages(): HasMany
    {
        return $this->hasMany(GroupMessage::class);
    }
}
