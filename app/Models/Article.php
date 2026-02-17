<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image_path',
        'section_id', 'vertical_id', 'status', 'access', 'is_featured',
        'meta_title', 'meta_description', 'published_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected $appends = ['featured_image_url'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function vertical(): BelongsTo
    {
        return $this->belongsTo(Vertical::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function contributors(): BelongsToMany
    {
        return $this->belongsToMany(Contributor::class)->withPivot('role');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopePublicAccess(Builder $query): Builder
    {
        return $query->where('access', 'public');
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        return $this->featured_image_path ? Storage::url($this->featured_image_path) : null;
    }
}
