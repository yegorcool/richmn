<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DecorLocation extends Model
{
    protected $fillable = [
        'name', 'slug', 'unlock_level', 'max_items', 'available_items',
    ];

    protected $casts = [
        'unlock_level' => 'integer',
        'max_items' => 'integer',
        'available_items' => 'array',
    ];

    public function decorItems(): HasMany
    {
        return $this->hasMany(DecorItem::class, 'location_id');
    }
}
