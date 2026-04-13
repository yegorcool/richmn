<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterRelationship extends Model
{
    protected $fillable = [
        'user_id', 'character_id', 'orders_completed', 'relationship_level',
    ];

    protected $casts = [
        'orders_completed' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function incrementOrders(): void
    {
        $this->increment('orders_completed');

        $thresholds = config('game.relationship_thresholds');
        $newLevel = 'new';

        if ($this->orders_completed >= $thresholds['loyal']) {
            $newLevel = 'loyal';
        } elseif ($this->orders_completed >= $thresholds['familiar']) {
            $newLevel = 'familiar';
        }

        if ($this->relationship_level !== $newLevel) {
            $this->update(['relationship_level' => $newLevel]);
        }
    }
}
