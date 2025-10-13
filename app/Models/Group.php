<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id',
        'name',
        'description',
        'is_public',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(GroupUser::class)
            ->withPivot('role', 'location', 'points')
            ->withTimestamps();
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(GroupTask::class);
    }

    public function meetups(): HasMany
    {
        return $this->hasMany(Meetup::class);
    }

    public function userAvailabilities(): HasMany
    {
        return $this->hasMany(UserAvailability::class);
    }
}
