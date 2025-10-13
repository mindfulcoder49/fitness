<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Comment $comment) {
            $comment->likes()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function group(): HasOneThrough
    {
        return $this->hasOneThrough(Group::class, Post::class, 'id', 'id', 'post_id', 'group_id');
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}
