<?php

namespace App\Services;

use App\Models\DecorItem;
use App\Models\DecorLocation;
use App\Models\User;
use Illuminate\Support\Collection;

class DecorService
{
    public function getLocationsForUser(User $user): Collection
    {
        $locations = DecorLocation::where('unlock_level', '<=', $user->level)->get();

        return $locations->map(function (DecorLocation $location) use ($user) {
            $placed = DecorItem::where('user_id', $user->id)
                ->where('location_id', $location->id)
                ->get();

            return [
                'id' => $location->id,
                'name' => $location->name,
                'slug' => $location->slug,
                'max_items' => $location->max_items,
                'available_items' => $location->available_items,
                'placed_items' => $placed,
            ];
        });
    }

    public function placeDecor(User $user, int $locationId, string $itemKey, int $styleVariant): array
    {
        $location = DecorLocation::find($locationId);
        if (!$location || $location->unlock_level > $user->level) {
            return ['success' => false, 'error' => 'Location not available'];
        }

        $validItem = collect($location->available_items)->firstWhere('key', $itemKey);
        if (!$validItem) {
            return ['success' => false, 'error' => 'Invalid item for this location'];
        }

        $decorItem = DecorItem::updateOrCreate(
            ['user_id' => $user->id, 'location_id' => $locationId, 'item_key' => $itemKey],
            ['style_variant' => $styleVariant, 'placed_at' => now()]
        );

        return ['success' => true, 'item' => $decorItem];
    }

    public function removeDecor(User $user, int $locationId, string $itemKey): array
    {
        $deleted = DecorItem::where('user_id', $user->id)
            ->where('location_id', $locationId)
            ->where('item_key', $itemKey)
            ->delete();

        return ['success' => $deleted > 0];
    }
}
