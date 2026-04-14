<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    /** @var array<string, array{label: string, hex: string, prompt: string}> */
    private const ACCENTS = [
        'hot_pink' => [
            'label' => 'Розовый',
            'hex' => '#ff4d9d',
            'prompt' => 'Primary hue: saturated hot pink and magenta — the main mass (body, casing, or glow) must read clearly as pink, not neutral or brown.',
        ],
        'lime_green' => [
            'label' => 'Зелёный',
            'hex' => '#6ee755',
            'prompt' => 'Primary hue: vivid lime and spring green — green dominates the silhouette so this theme reads as green.',
        ],
        'sky_blue' => [
            'label' => 'Голубой',
            'hex' => '#38b6ff',
            'prompt' => 'Primary hue: bright sky blue and azure — cool blue dominates the object.',
        ],
        'sunny_yellow' => [
            'label' => 'Жёлтый',
            'hex' => '#ffe135',
            'prompt' => 'Primary hue: clean sunny yellow and lemon — bright yellow dominates; avoid ochre or brown-yellow.',
        ],
        'orange' => [
            'label' => 'Оранжевый',
            'hex' => '#ff8c32',
            'prompt' => 'Primary hue: bold orange and tangerine — citrus orange dominates, not brown.',
        ],
        'violet' => [
            'label' => 'Фиолетовый',
            'hex' => '#a855f7',
            'prompt' => 'Primary hue: rich violet and purple — purple clearly identifies this theme.',
        ],
        'cyan' => [
            'label' => 'Бирюзовый',
            'hex' => '#22d3ee',
            'prompt' => 'Primary hue: electric cyan and aqua — turquoise-cyan dominates.',
        ],
        'magenta' => [
            'label' => 'Пурпурный',
            'hex' => '#e879f9',
            'prompt' => 'Primary hue: electric magenta and fuchsia — vivid purple-pink dominates.',
        ],
    ];

    protected $fillable = [
        'name', 'slug', 'accent_color', 'generator_name', 'generator_image_url',
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

    /** @return array<string, string> key => Russian label */
    public static function accentColorOptions(): array
    {
        $out = [];
        foreach (self::ACCENTS as $key => $meta) {
            $out[$key] = $meta['label'];
        }

        return $out;
    }

    /** @return list<string> */
    public static function accentColorKeys(): array
    {
        return array_keys(self::ACCENTS);
    }

    public static function normalizeAccentColor(?string $key): string
    {
        $k = $key ?? '';

        return isset(self::ACCENTS[$k]) ? $k : 'hot_pink';
    }

    public static function accentColorPromptFragment(string $key): string
    {
        return self::ACCENTS[self::normalizeAccentColor($key)]['prompt'];
    }

    public static function accentSwatchHex(string $key): string
    {
        return self::ACCENTS[self::normalizeAccentColor($key)]['hex'];
    }
}
