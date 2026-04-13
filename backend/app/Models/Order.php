<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'character_id', 'required_items',
        'reward', 'status', 'completed_at',
    ];

    protected $casts = [
        'required_items' => 'array',
        'reward' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function isFullyFulfilled(): bool
    {
        foreach ($this->required_items as $req) {
            if (!($req['fulfilled'] ?? false)) {
                return false;
            }
        }
        return true;
    }

    public function getWaitingMinutes(): float
    {
        return $this->created_at->diffInMinutes(now());
    }
}
