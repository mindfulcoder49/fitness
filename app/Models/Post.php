<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'content',
        'image_path',
        'video_path',
        'is_blog_post',
    ];

    protected $appends = ['image_url', 'video_url'];

    protected $casts = [
        'is_blog_post' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Post $post) {
            if ($post->image_path) {
                Storage::disk('public')->delete($post->image_path);
            }
            if ($post->video_path) {
                Storage::disk('public')->delete($post->video_path);
            }
            $post->comments()->delete();
            $post->likes()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::url($this->image_path) : null,
        );
    }

    protected function videoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->video_path ? Storage::url($this->video_path) : null,
        );
    }
}
