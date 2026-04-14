<?php

namespace App\Services;

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $mergeId = substr(md5(uniqid('', true)), 0, 8);
        Log::info("[MERGE:{$mergeId}] request user={$user->id} item1={$itemId1} item2={$itemId2}");

        $validation = $this->validateMerge($user, $itemId1, $itemId2);
        if (!$validation['valid']) {
            Log::warning("[MERGE:{$mergeId}] validation failed: {$validation['error']}");
            return $validation;
        }

        $item1 = $validation['item1'];
        $item2 = $validation['item2'];

        Log::info("[MERGE:{$mergeId}] validated item1=id:{$item1->id}(lvl={$item1->item_level},theme={$item1->theme_id},{$item1->grid_x}:{$item1->grid_y}) item2=id:{$item2->id}(lvl={$item2->item_level},{$item2->grid_x}:{$item2->grid_y})");

        return DB::transaction(function () use ($user, $item1, $item2, $mergeId) {
            $newLevel = $item1->item_level + 1;
            $targetX = $item2->grid_x;
            $targetY = $item2->grid_y;

            Log::info("[MERGE:{$mergeId}] deleting items {$item1->id},{$item2->id} → creating lvl={$newLevel} at {$targetX}:{$targetY}");

            $item1->delete();
            $item2->delete();

            $newItem = Item::create([
                'user_id' => $user->id,
                'theme_id' => $item1->theme_id,
                'item_level' => $newLevel,
                'grid_x' => $targetX,
                'grid_y' => $targetY,
            ]);

            Log::info("[MERGE:{$mergeId}] created newItem id={$newItem->id} lvl={$newLevel}, checking chain merge...");

            $allUserItems = Item::where('user_id', $user->id)->get();
            Log::info("[MERGE:{$mergeId}] all user items: " . $allUserItems->map(fn($i) => "id:{$i->id}(lvl={$i->item_level},{$i->grid_x}:{$i->grid_y})")->join(', '));

            $chainResult = $this->checkChainMerge($user, $newItem, $mergeId);
            $chainLength = 1 + ($chainResult['chain_length'] ?? 0);
            $finalItem = $chainResult['final_item'] ?? $newItem;

            Log::info("[MERGE:{$mergeId}] DONE finalItem id={$finalItem->id} lvl={$finalItem->item_level} chainLength={$chainLength} consumed=" . json_encode($chainResult['consumed_ids'] ?? []));

            $expGained = $this->calculateExperience($newLevel, $chainLength);
            $user->increment('experience', $expGained);
            $this->checkLevelUp($user);

            return [
                'valid' => true,
                'new_item' => $finalItem->fresh()->load('theme'),
                'chain_length' => $chainLength,
                'consumed_ids' => $chainResult['consumed_ids'] ?? [],
                'energy' => $this->energyService->getCurrentEnergy($user),
                'experience_gained' => $expGained,
            ];
        });
    }

    private function checkChainMerge(User $user, Item $item, string $mergeId = ''): array
    {
        $theme = $item->theme;
        if ($item->item_level >= $theme->getMaxLevel()) {
            Log::info("[MERGE:{$mergeId}] chain: item {$item->id} at max level, stop");
            return ['chain_length' => 0, 'final_item' => $item, 'consumed_ids' => []];
        }

        $adjacent = $this->getAdjacentItems($user, $item);
        Log::info("[MERGE:{$mergeId}] chain: item {$item->id}(lvl={$item->item_level},{$item->grid_x}:{$item->grid_y}) adjacent=[" . $adjacent->map(fn($a) => "id:{$a->id}(lvl={$a->item_level},{$a->grid_x}:{$a->grid_y})")->join(', ') . ']');

        $matchingItem = $adjacent->first(fn(Item $adj) => $adj->canMergeWith($item));

        if (!$matchingItem) {
            Log::info("[MERGE:{$mergeId}] chain: no matching adjacent, stop");
            return ['chain_length' => 0, 'final_item' => $item, 'consumed_ids' => []];
        }

        Log::info("[MERGE:{$mergeId}] chain: MATCH found id={$matchingItem->id}(lvl={$matchingItem->item_level}), merging → lvl=" . ($item->item_level + 1));

        $consumedId = $matchingItem->id;
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

        Log::info("[MERGE:{$mergeId}] chain: created chainItem id={$chainItem->id} lvl={$newLevel}, recursing...");

        $further = $this->checkChainMerge($user, $chainItem, $mergeId);

        return [
            'chain_length' => 1 + ($further['chain_length'] ?? 0),
            'final_item' => $further['final_item'] ?? $chainItem,
            'consumed_ids' => array_merge([$consumedId], $further['consumed_ids'] ?? []),
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
