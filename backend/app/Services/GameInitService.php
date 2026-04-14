<?php

namespace App\Services;

use App\Models\Generator;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameInitService
{
    /**
     * Fixed starter chains (same five as ItemDefinitionSeeder). Order = left-to-right on the field.
     * Not filtered by is_active so new players always get five generators when these rows exist.
     */
    private const STARTER_THEME_SLUGS = [
        'coffee',
        'bakery',
        'products',
        'fabrics',
        'pottery',
    ];

    /**
     * Starting positions for the 5 initial generators on the 6x8 grid.
     * Bottom row of five cells so the player has room above.
     */
    private const STARTER_POSITIONS = [
        ['x' => 0, 'y' => 5],
        ['x' => 1, 'y' => 5],
        ['x' => 2, 'y' => 5],
        ['x' => 3, 'y' => 5],
        ['x' => 4, 'y' => 5],
    ];

    /**
     * Create starter generators on the field for a new player.
     * Invoked from UserObserver::created. Idempotent if generators already exist.
     */
    public function seedStarterGenerators(User $user): bool
    {
        if ($user->generators()->exists()) {
            return false;
        }

        $themes = $this->starterThemes();
        if ($themes->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($user, $themes): void {
            foreach ($themes as $index => $theme) {
                $pos = self::STARTER_POSITIONS[$index] ?? self::STARTER_POSITIONS[0];

                $limit = $theme->generator_generation_limit ?: config('game.generator.default_limit', 5);
                $timeout = $theme->generator_generation_timeout ?: config('game.generator.default_timeout', 1800);
                $energyCost = $theme->generator_energy_cost ?: config('game.generator.default_energy_cost', 1);

                Generator::create([
                    'user_id' => $user->id,
                    'theme_id' => $theme->id,
                    'level' => 1,
                    'charges_left' => $limit,
                    'max_charges' => $limit,
                    'generation_limit' => $limit,
                    'generation_timeout_seconds' => $timeout,
                    'energy_cost' => $energyCost,
                    'grid_x' => $pos['x'],
                    'grid_y' => $pos['y'],
                ]);
            }
        });

        return true;
    }

    /**
     * Themes for the initial five generators (by slug). Missing slugs are skipped.
     *
     * @return \Illuminate\Support\Collection<int, Theme>
     */
    private function starterThemes()
    {
        $slugs = self::STARTER_THEME_SLUGS;

        $bySlug = Theme::query()
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        return collect($slugs)
            ->map(fn (string $slug) => $bySlug->get($slug))
            ->filter()
            ->values();
    }
}
