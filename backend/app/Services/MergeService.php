<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MergeService
{
    public function __construct(
        private EnergyService $energyService,
        private CharacterLineService $characterLineService,
    ) {}

    public function validateMerge(User $user, int $itemId1, int $itemId2): array
    {
        $item1 = Item::where('user_id', $user->id)->find($itemId1);
        $item2 = Item::where('user_id', $user->id)->find($itemId2);

        if (!$item1 || !$item2) {
            return ['valid' => false, 'error' => 'Items not found'];
        }

        if (!$item1->canMergeWith($item2)) {
            return ['valid' => false, 'error' => 'Items cannot be merged'];
        }

        $theme = $item1->theme;
        if ($item1->item_level >= $theme->getMaxLevel()) {
            return ['valid' => false, 'error' => 'Items already at max level'];
        }

        return ['valid' => true, 'item1' => $item1, 'item2' => $item2];
    }

    public function executeMerge(User $user, int $itemId1, int $itemId2): array
    {
        $validation = $this->validateMerge($user, $itemId1, $itemId2);
        if (!$validation['valid']) {
            return $validation;
        }

        $item1 = $validation['item1'];
        $item2 = $validation['item2'];

        return DB::transaction(function () use ($user, $item1, $item2) {
            $newLevel = $item1->item_level + 1;
            $targetX = $item2->grid_x;
            $targetY = $item2->grid_y;

            $item1->delete();
            $item2->delete();

            $newItem = Item::create([
                'user_id' => $user->id,
                'theme_id' => $item1->theme_id,
                'item_level' => $newLevel,
                'grid_x' => $targetX,
                'grid_y' => $targetY,
            ]);

            $chainResult = $this->checkChainMerge($user, $newItem);
            $chainLength = 1 + ($chainResult['chain_length'] ?? 0);
            $finalItem = $chainResult['final_item'] ?? $newItem;

            $expGained = $this->calculateExperience($newLevel, $chainLength);
            $user->increment('experience', $expGained);
            $this->checkLevelUp($user);

            return [
                'valid' => true,
                'new_item' => $finalItem->fresh()->load('theme'),
                'chain_length' => $chainLength,
                'energy' => $this->energyService->getCurrentEnergy($user),
                'experience_gained' => $expGained,
            ];
        });
    }

    private function checkChainMerge(User $user, Item $item): array
    {
        $theme = $item->theme;
        if ($item->item_level >= $theme->getMaxLevel()) {
            return ['chain_length' => 0, 'final_item' => $item];
        }

        $adjacent = $this->getAdjacentItems($user, $item);
        $matchingItem = $adjacent->first(fn(Item $adj) => $adj->canMergeWith($item));

        if (!$matchingItem) {
            return ['chain_length' => 0, 'final_item' => $item];
        }

        $newLevel = $item->item_level + 1;
        $targetX = $item->grid_x;
        $targetY = $item->grid_y;

        $matchingItem->delete();
        $item->delete();

        $chainItem = Item::create([
            'user_id' => $user->id,
            'theme_id' => $item->theme_id,
            'item_level' => $newLevel,
            'grid_x' => $targetX,
            'grid_y' => $targetY,
        ]);

        $further = $this->checkChainMerge($user, $chainItem);

        return [
            'chain_length' => 1 + ($further['chain_length'] ?? 0),
            'final_item' => $further['final_item'] ?? $chainItem,
        ];
    }

    private function getAdjacentItems(User $user, Item $item): \Illuminate\Support\Collection
    {
        $offsets = [[-1, 0], [1, 0], [0, -1], [0, 1]];
        $positions = [];

        foreach ($offsets as [$dx, $dy]) {
            $nx = $item->grid_x + $dx;
            $ny = $item->grid_y + $dy;
            if ($nx >= 0 && $nx < 6 && $ny >= 0 && $ny < 8) {
                $positions[] = [$nx, $ny];
            }
        }

        return Item::where('user_id', $user->id)
            ->where('id', '!=', $item->id)
            ->where(function ($query) use ($positions) {
                foreach ($positions as [$x, $y]) {
                    $query->orWhere(fn($q) => $q->where('grid_x', $x)->where('grid_y', $y));
                }
            })
            ->get();
    }

    private function calculateExperience(int $newLevel, int $chainLength): int
    {
        $base = $newLevel * 5;
        $chainBonus = max(0, ($chainLength - 1) * 10);
        return $base + $chainBonus;
    }

    private function checkLevelUp(User $user): void
    {
        $xpThreshold = $user->level * 100 + ($user->level * $user->level * 10);
        if ($user->experience >= $xpThreshold && $user->level < 50) {
            $user->increment('level');
            $user->update(['experience' => $user->experience - $xpThreshold]);
        }
    }
}
