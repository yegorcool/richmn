<?php

namespace App\Services;

use App\Models\Generator;
use App\Models\Item;
use App\Models\ItemDefinition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeneratorService
{
    public function __construct(
        private EnergyService $energyService,
    ) {}

    public function tapBatch(User $user, int $generatorId, int $count): array
    {
        $count = min(max($count, 1), 20);

        $generator = Generator::where('user_id', $user->id)->find($generatorId);
        if (!$generator) {
            return ['success' => false, 'error' => 'Generator not found'];
        }

        $generator->refreshCooldownIfExpired();

        if (!$generator->isReady()) {
            return [
                'success' => false,
                'error' => 'Generator not ready',
                'cooldown_until' => $generator->cooldown_until,
            ];
        }

        $energyCost = $generator->energy_cost ?: config('game.generator.default_energy_cost', 1);
        $currentEnergy = $this->energyService->getCurrentEnergy($user);
        if ($currentEnergy < $energyCost) {
            return ['success' => false, 'error' => 'Not enough energy'];
        }

        $maxTaps = min($count, intdiv($currentEnergy, $energyCost), $generator->charges_left);
        if ($maxTaps <= 0) {
            return ['success' => false, 'error' => 'Not enough energy'];
        }

        $items = [];

        DB::transaction(function () use ($user, $generator, $maxTaps, $energyCost, &$items) {
            $totalEnergyCost = $maxTaps * $energyCost;
            $this->energyService->spendEnergy($user, $totalEnergyCost, 'generator');

            for ($i = 0; $i < $maxTaps; $i++) {
                $emptySlot = $this->findEmptySlot($user, $generator);
                if (!$emptySlot) break;

                $spawnLevel = $this->getSpawnLevel($generator);
                $item = Item::create([
                    'user_id' => $user->id,
                    'theme_id' => $generator->theme_id,
                    'item_level' => $spawnLevel,
                    'grid_x' => $emptySlot['x'],
                    'grid_y' => $emptySlot['y'],
                ]);

                $itemDef = ItemDefinition::where('theme_id', $generator->theme_id)
                    ->where('level', $spawnLevel)
                    ->first();

                $item->load('theme');
                $itemData = $item->toArray();
                $itemData['theme_slug'] = $item->theme?->slug;
                $itemData['image_url'] = $itemDef?->image_path;
                $itemData['item_name'] = $itemDef?->name;
                $items[] = $itemData;

                $generator->decrement('charges_left');
                if ($generator->charges_left <= 0) {
                    $timeout = $generator->generation_timeout_seconds ?: config('game.generator.default_timeout', 1800);
                    $generator->update(['cooldown_until' => now()->addSeconds($timeout)]);
                    break;
                }
            }
        });

        return [
            'success' => true,
            'items' => $items,
            'tapped' => count($items),
            'generator' => $generator->fresh(),
            'energy' => $this->energyService->getCurrentEnergy($user),
            'energy_max' => config('game.energy.max'),
        ];
    }

    public function tap(User $user, int $generatorId): array
    {
        $generator = Generator::where('user_id', $user->id)->find($generatorId);
        if (!$generator) {
            return ['success' => false, 'error' => 'Generator not found'];
        }

        $generator->refreshCooldownIfExpired();

        if (!$generator->isReady()) {
            return [
                'success' => false,
                'error' => 'Generator not ready',
                'cooldown_until' => $generator->cooldown_until,
            ];
        }

        $energyCost = $generator->energy_cost ?: config('game.generator.default_energy_cost', 1);
        $currentEnergy = $this->energyService->getCurrentEnergy($user);
        if ($currentEnergy < $energyCost) {
            return ['success' => false, 'error' => 'Not enough energy'];
        }

        $emptySlot = $this->findEmptySlot($user, $generator);
        if (!$emptySlot) {
            return ['success' => false, 'error' => 'No empty slots'];
        }

        $this->energyService->spendEnergy($user, $energyCost, 'generator');

        $spawnLevel = $this->getSpawnLevel($generator);
        $item = Item::create([
            'user_id' => $user->id,
            'theme_id' => $generator->theme_id,
            'item_level' => $spawnLevel,
            'grid_x' => $emptySlot['x'],
            'grid_y' => $emptySlot['y'],
        ]);

        $generator->decrement('charges_left');
        $timeout = $generator->generation_timeout_seconds ?: config('game.generator.default_timeout', 1800);

        if ($generator->charges_left <= 0) {
            $generator->update([
                'cooldown_until' => now()->addSeconds($timeout),
            ]);
        }

        $itemDef = ItemDefinition::where('theme_id', $generator->theme_id)
            ->where('level', $spawnLevel)
            ->first();

        $item->load('theme');
        $itemData = $item->toArray();
        $itemData['theme_slug'] = $item->theme?->slug;
        $itemData['image_url'] = $itemDef?->image_path;
        $itemData['item_name'] = $itemDef?->name;

        return [
            'success' => true,
            'item' => $itemData,
            'item_definition' => $itemDef,
            'generator' => $generator->fresh(),
            'energy' => $this->energyService->getCurrentEnergy($user),
            'energy_max' => config('game.energy.max'),
        ];
    }

    public function mergeGenerators(User $user, int $genId1, int $genId2): array
    {
        $gen1 = Generator::where('user_id', $user->id)->find($genId1);
        $gen2 = Generator::where('user_id', $user->id)->find($genId2);

        if (!$gen1 || !$gen2 || !$gen1->canMergeWith($gen2)) {
            return ['success' => false, 'error' => 'Cannot merge generators'];
        }

        $newLevel = $gen1->level + 1;
        $targetX = $gen2->grid_x;
        $targetY = $gen2->grid_y;

        $theme = $gen1->theme;
        $limit = $theme->generator_generation_limit ?: config('game.generator.default_limit', 5);
        $timeout = $theme->generator_generation_timeout ?: config('game.generator.default_timeout', 1800);
        $energyCost = $theme->generator_energy_cost ?: config('game.generator.default_energy_cost', 1);

        $gen1->delete();
        $gen2->delete();

        $newGenerator = Generator::create([
            'user_id' => $user->id,
            'theme_id' => $gen1->theme_id,
            'level' => $newLevel,
            'charges_left' => $limit + $newLevel,
            'max_charges' => $limit + $newLevel,
            'generation_limit' => $limit,
            'generation_timeout_seconds' => $timeout,
            'energy_cost' => $energyCost,
            'grid_x' => $targetX,
            'grid_y' => $targetY,
        ]);

        return ['success' => true, 'generator' => $newGenerator];
    }

    private function findEmptySlot(User $user, Generator $generator): ?array
    {
        $occupied = Item::where('user_id', $user->id)
            ->get(['grid_x', 'grid_y'])
            ->map(fn($i) => "{$i->grid_x},{$i->grid_y}")
            ->toBase()
            ->merge(
                Generator::where('user_id', $user->id)
                    ->get(['grid_x', 'grid_y'])
                    ->map(fn($g) => "{$g->grid_x},{$g->grid_y}")
                    ->toBase()
            )
            ->toArray();

        $offsets = [[-1, 0], [1, 0], [0, -1], [0, 1], [-1, -1], [1, -1], [-1, 1], [1, 1]];

        foreach ($offsets as [$dx, $dy]) {
            $x = $generator->grid_x + $dx;
            $y = $generator->grid_y + $dy;
            if ($x >= 0 && $x < 6 && $y >= 0 && $y < 8 && !in_array("{$x},{$y}", $occupied)) {
                return ['x' => $x, 'y' => $y];
            }
        }

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 6; $x++) {
                if (!in_array("{$x},{$y}", $occupied)) {
                    return ['x' => $x, 'y' => $y];
                }
            }
        }

        return null;
    }

    private function getSpawnLevel(Generator $generator): int
    {
        $maxSpawn = min($generator->level, 3);
        $weights = array_fill(1, $maxSpawn, 0);

        foreach ($weights as $level => &$weight) {
            $weight = max(1, $maxSpawn - $level + 1) * 10;
        }

        $total = array_sum($weights);
        $rand = rand(1, $total);
        $cumulative = 0;

        foreach ($weights as $level => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) return $level;
        }

        return 1;
    }
}
