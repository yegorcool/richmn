<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Chest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'source', 'contents', 'unlock_at', 'opened_at',
    ];

    protected $casts = [
        'contents' => 'array',
        'unlock_at' => 'datetime',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function canOpen(): bool
    {
        if ($this->opened_at !== null) return false;
        if ($this->unlock_at === null) return true;
        return $this->unlock_at->isPast();
    }
}
