<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meetup extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'description',
        'location',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
