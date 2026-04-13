<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterRelationship;
use App\Models\Item;
use App\Models\Order;
use App\Models\Theme;
use App\Models\User;
use Illuminate\Support\Collection;

class OrderService
{
    public function getActiveOrders(User $user): Collection
    {
        return $user->activeOrders()->with('character')->get();
    }

    public function generateOrder(User $user, Character $character): Order
    {
        $playerLevel = $user->level ?? 1;
        $difficulty = $this->scaleOrderDifficulty($user);
        $availableThemes = Theme::where('is_active', true)
            ->where('unlock_level', '<=', $playerLevel)
            ->get();

        $itemCount = $difficulty <= 3 ? 1 : ($difficulty <= 6 ? rand(1, 2) : rand(1, 3));
        $requiredItems = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $theme = $availableThemes->random();
            $maxLevel = min($difficulty + 1, $theme->getMaxLevel());
            $minLevel = max(1, $maxLevel - 3);
            $level = rand($minLevel, $maxLevel);

            $requiredItems[] = [
                'theme_id' => $theme->id,
                'theme_slug' => $theme->slug,
                'theme_name' => $theme->name,
                'item_level' => $level,
                'item_name' => $theme->getItemNameAtLevel($level),
                'fulfilled' => false,
            ];
        }

        $reward = $this->calculateReward($requiredItems, $playerLevel);

        return Order::create([
            'user_id' => $user->id,
            'character_id' => $character->id,
            'required_items' => $requiredItems,
            'reward' => $reward,
            'status' => 'active',
        ]);
    }

    public function submitItem(User $user, Order $order, int $itemId): array
    {
        if ($order->user_id !== $user->id || $order->status !== 'active') {
            return ['success' => false, 'error' => 'Invalid order'];
        }

        $item = Item::where('user_id', $user->id)->find($itemId);
        if (!$item) {
            return ['success' => false, 'error' => 'Item not found'];
        }

        $requiredItems = $order->required_items;
        $matched = false;

        foreach ($requiredItems as &$req) {
            if (!$req['fulfilled']
                && $req['theme_id'] == $item->theme_id
                && $req['item_level'] == $item->item_level
            ) {
                $req['fulfilled'] = true;
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            return ['success' => false, 'error' => 'Item does not match any requirement'];
        }

        $item->delete();
        $order->update(['required_items' => $requiredItems]);

        if ($order->isFullyFulfilled()) {
            return $this->completeOrder($user, $order->fresh());
        }

        return [
            'success' => true,
            'partial' => true,
            'order' => $order->fresh(),
        ];
    }

    public function completeOrder(User $user, Order $order): array
    {
        $reward = $order->reward;
        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $user->increment('coins', $reward['coins'] ?? 0);
        $user->increment('experience', $reward['experience'] ?? 0);

        $rel = CharacterRelationship::firstOrCreate(
            ['user_id' => $user->id, 'character_id' => $order->character_id],
            ['orders_completed' => 0, 'relationship_level' => 'new']
        );
        $rel->incrementOrders();

        $this->checkLevelUp($user);

        $activeCount = $user->activeOrders()->count();
        $needsNewOrder = $activeCount < config('game.orders.max_active');

        return [
            'success' => true,
            'partial' => false,
            'reward' => $reward,
            'order' => $order->fresh(),
            'needs_new_order' => $needsNewOrder,
        ];
    }

    public function ensureActiveOrders(User $user): void
    {
        $activeCount = $user->activeOrders()->count();
        $maxOrders = config('game.orders.max_active');

        if ($activeCount >= $maxOrders) return;

        $playerLevel = $user->level ?? 1;

        $availableCharacters = Character::where('is_active', true)
            ->where('unlock_level', '<=', $playerLevel)
            ->get();

        if ($availableCharacters->isEmpty()) return;

        $needed = $maxOrders - $activeCount;
        for ($i = 0; $i < $needed; $i++) {
            $character = $availableCharacters->random();
            $this->generateOrder($user, $character);
        }
    }

    private function scaleOrderDifficulty(User $user): int
    {
        return min(10, max(1, intdiv($user->level ?? 1, 2) + 1));
    }

    private function calculateReward(array $requiredItems, int $playerLevel): array
    {
        $totalLevel = array_sum(array_column($requiredItems, 'item_level'));
        $itemCount = count($requiredItems);

        return [
            'coins' => ($totalLevel * 10) + ($itemCount * 20) + ($playerLevel * 5),
            'experience' => ($totalLevel * 5) + ($itemCount * 10),
            'decor_resource' => $totalLevel >= 8 ? rand(1, 3) : 0,
        ];
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
