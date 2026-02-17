<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Contributor extends Model
{
    protected $fillable = ['name', 'slug', 'bio', 'photo_path', 'user_id'];

    protected $appends = ['photo_url'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class)->withPivot('role');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }
}
