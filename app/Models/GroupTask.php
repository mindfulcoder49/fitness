<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'title',
        'description',
        'is_current',
    ];

    protected $casts = [
        'is_current' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }
}
