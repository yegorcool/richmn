<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Theme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    use ResolvesUser;

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $discoveredLevels = Item::where('user_id', $user->id)
            ->selectRaw('theme_id, MAX(item_level) as max_level')
            ->groupBy('theme_id')
            ->pluck('max_level', 'theme_id');

        $themes = Theme::where('is_active', true)
            ->where('unlock_level', '<=', $user->level)
            ->get()
            ->map(function (Theme $theme) use ($discoveredLevels) {
                $maxDiscovered = $discoveredLevels[$theme->id] ?? 0;
                return [
                    'id' => $theme->id,
                    'name' => $theme->name,
                    'slug' => $theme->slug,
                    'chain' => collect($theme->chain_config)->map(function ($item) use ($maxDiscovered) {
                        return array_merge($item, [
                            'discovered' => $item['level'] <= $maxDiscovered,
                        ]);
                    }),
                    'completion' => $theme->getMaxLevel() > 0
                        ? round($maxDiscovered / $theme->getMaxLevel() * 100)
                        : 0,
                ];
            });

        return response()->json(['collection' => $themes]);
    }
}
