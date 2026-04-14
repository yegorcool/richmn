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
            'prompt' => 'Accent hue: soft rose-pink and muted magenta — use for glow, trim, glaze, particles, or supporting props; keep saturation moderate, not neon; do not recolor brown or natural subjects (e.g. beans, wood) into pink.',
        ],
        'lime_green' => [
            'label' => 'Зелёный',
            'hex' => '#6ee755',
            'prompt' => 'Accent hue: fresh spring green with a touch of yellow, not neon lime — for highlights, energy, or accents; keep plants/food their natural greens where that reads better.',
        ],
        'sky_blue' => [
            'label' => 'Голубой',
            'hex' => '#38b6ff',
            'prompt' => 'Accent hue: clear sky blue, slightly softened — for glass shine, vapor, trim, or gentle sparkle; water and sky may stay naturally blue without forcing the whole icon monochrome.',
        ],
        'sunny_yellow' => [
            'label' => 'Жёлтый',
            'hex' => '#ffe135',
            'prompt' => 'Accent hue: warm butter or soft lemon, not harsh highlighter yellow — for cheerful highlights or packaging; golden baked goods stay warm golden.',
        ],
        'orange' => [
            'label' => 'Оранжевый',
            'hex' => '#ff8c32',
            'prompt' => 'Accent hue: warm tangerine or apricot, not traffic-cone orange — for citrus zest, trim, or glow; brown foods (coffee, chocolate, crust) keep believable browns.',
        ],
        'violet' => [
            'label' => 'Фиолетовый',
            'hex' => '#a855f7',
            'prompt' => 'Accent hue: soft violet and lavender-purple — for sheen, berries, or decorative accents; avoid electric purple; other subjects keep natural hues.',
        ],
        'cyan' => [
            'label' => 'Бирюзовый',
            'hex' => '#22d3ee',
            'prompt' => 'Accent hue: calm aqua or teal-leaning cyan, not fluorescent — for water sparkle, ice edges, tech glow, or trim; do not wash out every material into flat cyan.',
        ],
        'magenta' => [
            'label' => 'Пурпурный',
            'hex' => '#e879f9',
            'prompt' => 'Accent hue: soft fuchsia or orchid, not neon magenta — for fantasy accents or highlights; the main product stays recognizable and naturally colored when needed.',
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
