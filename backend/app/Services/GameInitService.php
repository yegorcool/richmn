<?php

namespace App\Services;

use App\Models\Generator;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameInitService
{
    /**
     * Starting positions for the 3 initial generators on the 6x8 grid.
     * Spread across the bottom half so the player has room above.
     */
    private const STARTER_POSITIONS = [
        ['x' => 1, 'y' => 5],
        ['x' => 3, 'y' => 5],
        ['x' => 5, 'y' => 5],
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
     * Prefer active themes; if none (e.g. admin disabled all), fall back so new players are not stuck with an empty field.
     *
     * @return \Illuminate\Support\Collection<int, Theme>
     */
    private function starterThemes()
    {
        $active = Theme::query()
            ->where('is_active', true)
            ->orderBy('unlock_level')
            ->limit(3)
            ->get();

        if ($active->isNotEmpty()) {
            return $active;
        }

        return Theme::query()
            ->orderBy('unlock_level')
            ->limit(3)
            ->get();
    }
}
