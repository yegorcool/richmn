<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterLine extends Model
{
    protected $fillable = [
        'character_id', 'trigger', 'conditions', 'text',
        'priority', 'max_shows', 'cooldown_hours',
    ];

    protected $casts = [
        'conditions' => 'array',
        'priority' => 'integer',
        'max_shows' => 'integer',
        'cooldown_hours' => 'integer',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
