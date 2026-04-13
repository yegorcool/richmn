<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemDefinition;
use App\Models\User;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    private static function definitionImageHref(?ItemDefinition $def): ?string
    {
        if (!$def || !$def->image_url) {
            return null;
        }

        $path = $def->image_path;

        return str_starts_with($path, 'http') ? $path : url($path);
    }

    public function index(Request $request)
    {
        $query = User::query()->orderByDesc('last_activity');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('platform_id', $search);
            });
        }

        $users = $query->paginate(50);
        return view('admin.users', compact('users'));
    }

    public function show(User $user)
    {
        $user->load(['orders' => fn($q) => $q->latest()->limit(20), 'characterRelationships.character', 'streak']);

        $gridW = config('game.grid.width');
        $gridH = config('game.grid.height');

        $fieldItems = $user->items()->with('theme:id,slug,name')->orderBy('grid_y')->orderBy('grid_x')->get();
        $fieldGenerators = $user->generators()->with('theme:id,slug,name')->orderBy('grid_y')->orderBy('grid_x')->get();

        $themeIds = $fieldItems->pluck('theme_id')->merge($fieldGenerators->pluck('theme_id'))->unique()->values();
        $defs = $themeIds->isEmpty()
            ? collect()
            : ItemDefinition::query()
                ->whereIn('theme_id', $themeIds)
                ->get()
                ->keyBy(fn ($d) => $d->theme_id . '_' . $d->level);

        foreach ($fieldItems as $item) {
            $def = $defs->get($item->theme_id . '_' . $item->item_level);
            $item->setAttribute('definition_name', $def?->name);
            $item->setAttribute('image_href', self::definitionImageHref($def));
        }
        foreach ($fieldGenerators as $gen) {
            $def = $defs->get($gen->theme_id . '_' . $gen->level);
            $gen->setAttribute('definition_name', $def?->name);
            $gen->setAttribute('image_href', self::definitionImageHref($def));
        }

        $itemGridCells = [];
        $generatorGridCells = [];
        for ($y = 0; $y < $gridH; $y++) {
            $itemGridCells[$y] = array_fill(0, $gridW, null);
            $generatorGridCells[$y] = array_fill(0, $gridW, null);
        }
        foreach ($fieldItems as $item) {
            if ($item->grid_y >= 0 && $item->grid_y < $gridH && $item->grid_x >= 0 && $item->grid_x < $gridW) {
                $itemGridCells[$item->grid_y][$item->grid_x] = $item;
            }
        }
        foreach ($fieldGenerators as $gen) {
            if ($gen->grid_y >= 0 && $gen->grid_y < $gridH && $gen->grid_x >= 0 && $gen->grid_x < $gridW) {
                $generatorGridCells[$gen->grid_y][$gen->grid_x] = $gen;
            }
        }

        return view('admin.user-detail', compact(
            'user',
            'gridW',
            'gridH',
            'fieldItems',
            'fieldGenerators',
            'itemGridCells',
            'generatorGridCells',
        ));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'level' => 'sometimes|integer|min:1|max:50',
            'energy' => 'sometimes|integer|min:0|max:999',
            'coins' => 'sometimes|integer|min:0',
            'experience' => 'sometimes|integer|min:0',
        ]);

        $user->update($validated);
        return back()->with('success', 'Пользователь обновлён');
    }
}
