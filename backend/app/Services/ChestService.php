<?php

namespace App\Services;

use App\Models\Chest;
use App\Models\Theme;
use App\Models\User;

class ChestService
{
    public function createChest(User $user, string $type, string $source): Chest
    {
        $timer = config("game.chest_timers.{$type}", 3600);

        return Chest::create([
            'user_id' => $user->id,
            'type' => $type,
            'source' => $source,
            'unlock_at' => $timer > 0 ? now()->addSeconds($timer) : null,
        ]);
    }

    public function openChest(User $user, Chest $chest, bool $adSkip = false): array
    {
        if ($chest->user_id !== $user->id) {
            return ['success' => false, 'error' => 'Not your chest'];
        }

        if ($chest->opened_at !== null) {
            return ['success' => false, 'error' => 'Already opened'];
        }

        if (!$adSkip && !$chest->canOpen()) {
            return ['success' => false, 'error' => 'Chest not ready', 'unlock_at' => $chest->unlock_at];
        }

        $loot = $this->generateLoot($chest->type, $user->level);

        $chest->update([
            'contents' => $loot,
            'opened_at' => now(),
        ]);

        $this->distributeLoot($user, $loot);

        return ['success' => true, 'loot' => $loot];
    }

    private function generateLoot(string $type, int $playerLevel): array
    {
        $loot = ['coins' => 0, 'energy' => 0, 'items' => []];

        $availableThemes = Theme::where('is_active', true)
            ->where('unlock_level', '<=', $playerLevel)
            ->get();

        match ($type) {
            'small' => $this->generateSmallLoot($loot, $availableThemes),
            'medium' => $this->generateMediumLoot($loot, $availableThemes),
            'large' => $this->generateLargeLoot($loot, $availableThemes, $playerLevel),
            'super' => $this->generateSuperLoot($loot, $availableThemes, $playerLevel),
        };

        return $loot;
    }

    private function generateSmallLoot(array &$loot, $themes): void
    {
        $loot['coins'] = rand(20, 50);
        if ($themes->isNotEmpty()) {
            $theme = $themes->random();
            $loot['items'][] = [
                'theme_id' => $theme->id,
                'theme_slug' => $theme->slug,
                'level' => rand(1, 3),
            ];
        }
    }

    private function generateMediumLoot(array &$loot, $themes): void
    {
        $loot['coins'] = rand(50, 120);
        $loot['energy'] = 5;
        for ($i = 0; $i < 2; $i++) {
            if ($themes->isNotEmpty()) {
                $theme = $themes->random();
                $loot['items'][] = [
                    'theme_id' => $theme->id,
                    'theme_slug' => $theme->slug,
                    'level' => rand(2, 4),
                ];
            }
        }
    }

    private function generateLargeLoot(array &$loot, $themes, int $level): void
    {
        $loot['coins'] = rand(100, 250);
        $loot['energy'] = 10;
        $maxItemLevel = min(6, intdiv($level, 3) + 3);
        for ($i = 0; $i < 3; $i++) {
            if ($themes->isNotEmpty()) {
                $theme = $themes->random();
                $loot['items'][] = [
                    'theme_id' => $theme->id,
                    'theme_slug' => $theme->slug,
                    'level' => rand(3, $maxItemLevel),
                ];
            }
        }
    }

    private function generateSuperLoot(array &$loot, $themes, int $level): void
    {
        $loot['coins'] = rand(200, 500);
        $loot['energy'] = 20;
        $maxItemLevel = min(8, intdiv($level, 2) + 3);
        for ($i = 0; $i < 4; $i++) {
            if ($themes->isNotEmpty()) {
                $theme = $themes->random();
                $loot['items'][] = [
                    'theme_id' => $theme->id,
                    'theme_slug' => $theme->slug,
                    'level' => rand(3, $maxItemLevel),
                ];
            }
        }
    }

    private function distributeLoot(User $user, array $loot): void
    {
        if ($loot['coins'] > 0) {
            $user->increment('coins', $loot['coins']);
        }
        if ($loot['energy'] > 0) {
            app(EnergyService::class)->refillFromBonus($user, 'chest', $loot['energy']);
        }
    }
}
