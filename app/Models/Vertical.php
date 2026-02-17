<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Vertical extends Model
{
    protected $fillable = ['section_id', 'name', 'slug', 'description', 'image_path'];

    protected $appends = ['image_url'];

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }
}
