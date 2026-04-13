<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Item extends Model
{
    protected $fillable = [
        'user_id', 'theme_id', 'item_level', 'grid_x', 'grid_y',
    ];

    protected $casts = [
        'item_level' => 'integer',
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

    public function canMergeWith(Item $other): bool
    {
        return $this->theme_id === $other->theme_id
            && $this->item_level === $other->item_level
            && $this->id !== $other->id;
    }
}
