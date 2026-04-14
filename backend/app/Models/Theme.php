<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'name', 'slug', 'generator_name', 'generator_image_url',
        'unlock_level', 'chain_config', 'is_active',
        'generator_energy_cost', 'generator_generation_limit', 'generator_generation_timeout',
    ];

    protected $casts = [
        'chain_config' => 'array',
        'is_active' => 'boolean',
        'unlock_level' => 'integer',
        'generator_energy_cost' => 'integer',
        'generator_generation_limit' => 'integer',
        'generator_generation_timeout' => 'integer',
    ];

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function generators(): HasMany
    {
        return $this->hasMany(Generator::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function itemDefinitions(): HasMany
    {
        return $this->hasMany(ItemDefinition::class)->orderBy('level');
    }

    public function getMaxLevel(): int
    {
        return count($this->chain_config);
    }

    /** Public URL for generator icon (upload, external, or null). */
    public function getGeneratorImagePathAttribute(): ?string
    {
        if (!$this->generator_image_url) {
            return null;
        }

        if (str_starts_with($this->generator_image_url, 'http')) {
            return $this->generator_image_url;
        }

        return '/storage/' . $this->generator_image_url;
    }

    public function getItemNameAtLevel(int $level): ?string
    {
        return $this->chain_config[$level - 1]['name'] ?? null;
    }
}
