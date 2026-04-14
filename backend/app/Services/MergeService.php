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

            $expGained = $this->calculateExperience($newLevel);
            $user->increment('experience', $expGained);
            $this->checkLevelUp($user);

            return [
                'valid' => true,
                'new_item' => $newItem->fresh()->load('theme'),
                'energy' => $this->energyService->getCurrentEnergy($user),
                'experience_gained' => $expGained,
            ];
        });
    }

    private function calculateExperience(int $newLevel): int
    {
        return $newLevel * 5;
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
