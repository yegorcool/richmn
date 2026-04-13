<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Generator extends Model
{
    protected $fillable = [
        'user_id', 'theme_id', 'type', 'level',
        'charges_left', 'max_charges', 'cooldown_until',
        'generation_limit', 'generation_timeout_seconds', 'energy_cost',
        'grid_x', 'grid_y',
    ];

    protected $casts = [
        'level' => 'integer',
        'charges_left' => 'integer',
        'max_charges' => 'integer',
        'cooldown_until' => 'datetime',
        'generation_limit' => 'integer',
        'generation_timeout_seconds' => 'integer',
        'energy_cost' => 'integer',
        'grid_x' => 'integer',
        'grid_y' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function isReady(): bool
    {
        if ($this->type === 'chargeable') {
            return $this->charges_left > 0;
        }
        return $this->cooldown_until === null || $this->cooldown_until->isPast();
    }

    public function canMergeWith(Generator $other): bool
    {
        return $this->theme_id === $other->theme_id
            && $this->level === $other->level
            && $this->id !== $other->id;
    }
}
