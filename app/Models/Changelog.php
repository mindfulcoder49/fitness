<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Changelog extends Model
{
    use HasFactory;

    protected $fillable = ['release_date', 'changes'];

    protected $casts = [
        'release_date' => 'date',
        'changes' => 'array',
    ];

    public function readByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
