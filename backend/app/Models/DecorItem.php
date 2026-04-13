<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DecorItem extends Model
{
    protected $fillable = [
        'user_id', 'location_id', 'item_key', 'style_variant', 'placed_at',
    ];

    protected $casts = [
        'style_variant' => 'integer',
        'placed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(DecorLocation::class, 'location_id');
    }
}
