<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemDefinition extends Model
{
    protected $fillable = [
        'theme_id', 'level', 'name', 'slug', 'image_url',
    ];

    protected $casts = [
        'level' => 'integer',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function getImagePathAttribute(): ?string
    {
        if (!$this->image_url) {
            return null;
        }

        if (str_starts_with($this->image_url, 'http')) {
            return $this->image_url;
        }

        return '/storage/' . $this->image_url;
    }
}
