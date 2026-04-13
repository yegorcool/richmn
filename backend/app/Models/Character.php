<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Character extends Model
{
    protected $fillable = [
        'name', 'slug', 'theme_id', 'personality',
        'speech_style', 'avatar_path', 'unlock_level', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'unlock_level' => 'integer',
    ];

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CharacterLine::class);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(CharacterRelationship::class);
    }
}
