<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyChallenge extends Model
{
    protected $fillable = [
        'user_id', 'date', 'challenges', 'completed',
    ];

    protected $casts = [
        'date' => 'date',
        'challenges' => 'array',
        'completed' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
