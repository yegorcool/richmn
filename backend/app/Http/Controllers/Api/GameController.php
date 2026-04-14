<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Generator;
use App\Models\Item;
use App\Models\ItemDefinition;
use App\Services\CharacterLineService;
use App\Services\EnergyService;
use App\Services\GameInitService;
use App\Services\GeneratorService;
use App\Services\MergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GameController extends Controller
{
    use ResolvesUser;

    public function state(Request $request, EnergyService $energy, GameInitService $gameInit): JsonResponse
    {
        $user = $this->user($request);

        $gameInit->seedStarterGenerators($user);

        $items = $user->items()->with('theme:id,slug,name,chain_config')->get();
        $itemDefinitions = ItemDefinition::whereIn(
            'theme_id',
            $items->pluck('theme_id')->unique()->merge(
                $user->generators()->pluck('theme_id')->unique()
            )
        )->get()->keyBy(fn($d) => $d->theme_id . '_' . $d->level);

        $itemsWithImages = $items->map(function ($item) use ($itemDefinitions) {
            $def = $itemDefinitions->get($item->theme_id . '_' . $item->item_level);
            $itemArray = $item->toArray();
            $itemArray['theme_slug'] = $item->theme?->slug;
            $itemArray['image_url'] = $def?->image_path;
            $itemArray['item_name'] = $def?->name;
            return $itemArray;
        });

        $generators = $user->generators()->with('theme:id,slug,name,generator_image_url')->get();
        foreach ($generators as $generator) {
            $generator->refreshCooldownIfExpired();
        }

        return response()->json([
            'items' => $itemsWithImages,
            'generators' => $generators->map(fn (Generator $g) => $this->generatorToClientArray($g)),
            'energy' => $energy->getCurrentEnergy($user),
            'energy_max' => config('game.energy.max'),
            'energy_recovery_seconds' => $energy->getRecoverySecondsRemaining($user),
            'grid' => config('game.grid'),
            'item_definitions' => ItemDefinition::with('theme:id,slug')
                ->get()
                ->groupBy('theme_id')
                ->map(fn($defs) => $defs->map(fn($d) => [
                    'level' => $d->level,
                    'name' => $d->name,
                    'slug' => $d->slug,
                    'image_url' => $d->image_path,
                ])),
        ]);
    }

    public function merge(Request $request, MergeService $merge, CharacterLineService $cls): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'item_id_1' => 'required|integer',
            'item_id_2' => 'required|integer',
        ]);

        Log::info("[MERGE:controller] incoming request user={$user->id} item_id_1={$validated['item_id_1']} item_id_2={$validated['item_id_2']}");

        $result = $merge->executeMerge($user, $validated['item_id_1'], $validated['item_id_2']);

        if (!$result['valid']) {
            Log::warning("[MERGE:controller] merge failed: {$result['error']}");
            return response()->json(['error' => $result['error']], 422);
        }

        $characterLine = null;
        $trigger = $result['chain_length'] > 1 ? 'chain_merge' : 'merge_nearby';
        $activeOrders = $user->activeOrders()->with('character')->get();

        if ($activeOrders->isNotEmpty()) {
            $order = $activeOrders->first();
            $character = $order->character;
            $context = $cls->buildContext($user, $character, $order);
            $line = $cls->getLine($character, $trigger, $context, $user);
            if ($line) {
                $cls->recordShow($user, $line);
                $characterLine = ['id' => $line->id, 'character_id' => $line->character_id, 'text' => $line->text];
            }
        }

        $newItem = $result['new_item'];
        $itemDef = ItemDefinition::where('theme_id', $newItem->theme_id)
            ->where('level', $newItem->item_level)
            ->first();

        $response = [
            'new_item' => array_merge($newItem->toArray(), [
                'theme_slug' => $newItem->theme?->slug,
                'image_url' => $itemDef?->image_path,
                'item_name' => $itemDef?->name,
            ]),
            'chain_length' => $result['chain_length'],
            'consumed_ids' => $result['consumed_ids'] ?? [],
            'energy' => $result['energy'],
            'experience_gained' => $result['experience_gained'],
            'character_line' => $characterLine,
        ];

        Log::info("[MERGE:controller] response new_item_id={$newItem->id} lvl={$newItem->item_level} chain={$result['chain_length']} consumed=" . json_encode($result['consumed_ids'] ?? []));

        return response()->json($response);
    }

    public function tapGenerator(Request $request, GeneratorService $generators, EnergyService $energy): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'generator_id' => 'required|integer',
        ]);

        $result = $generators->tap($user, $validated['generator_id']);

        if (!$result['success']) {
            if ($result['error'] === 'Not enough energy') {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 403);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'cooldown_until' => $result['cooldown_until'] ?? null,
            ]);
        }

        if ($result['success'] && isset($result['generator'])) {
            $result['generator'] = $this->generatorToClientArray($result['generator']);
        }

        return response()->json($result);
    }

    public function tapGeneratorBatch(Request $request, GeneratorService $generators): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'generator_id' => 'required|integer',
            'count' => 'required|integer|min:1|max:20',
        ]);

        $result = $generators->tapBatch($user, $validated['generator_id'], $validated['count']);

        if (!$result['success']) {
            if ($result['error'] === 'Not enough energy') {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 403);
            }

            return response()->json([
                'success' => false,
                'error' => $result['error'],
                'cooldown_until' => $result['cooldown_until'] ?? null,
            ]);
        }

        if ($result['success'] && isset($result['generator'])) {
            $result['generator'] = $this->generatorToClientArray($result['generator']);
        }

        return response()->json($result);
    }

    public function moveBatch(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'moves' => 'required|array|max:20',
            'moves.*.type' => 'required|in:item,generator',
            'moves.*.id' => 'required|integer',
            'moves.*.grid_x' => 'required|integer|min:0|max:5',
            'moves.*.grid_y' => 'required|integer|min:0|max:7',
        ]);

        foreach ($validated['moves'] as $move) {
            if ($move['type'] === 'item') {
                $item = Item::where('user_id', $user->id)->find($move['id']);
                if ($item) {
                    $item->update(['grid_x' => $move['grid_x'], 'grid_y' => $move['grid_y']]);
                }
            } else {
                $generator = Generator::where('user_id', $user->id)->find($move['id']);
                if ($generator) {
                    $generator->update(['grid_x' => $move['grid_x'], 'grid_y' => $move['grid_y']]);
                }
            }
        }

        return response()->json(['success' => true]);
    }

    public function moveItem(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'item_id' => 'required|integer',
            'grid_x' => 'required|integer|min:0|max:5',
            'grid_y' => 'required|integer|min:0|max:7',
        ]);

        $item = Item::where('user_id', $user->id)->find($validated['item_id']);
        if (!$item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $occupied = Item::where('user_id', $user->id)
            ->where('id', '!=', $item->id)
            ->where('grid_x', $validated['grid_x'])
            ->where('grid_y', $validated['grid_y'])
            ->exists();

        if ($occupied) {
            return response()->json(['error' => 'Cell occupied'], 422);
        }

        $item->update([
            'grid_x' => $validated['grid_x'],
            'grid_y' => $validated['grid_y'],
        ]);

        return response()->json(['success' => true, 'item' => $item]);
    }

    public function moveGenerator(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'generator_id' => 'required|integer',
            'grid_x' => 'required|integer|min:0|max:5',
            'grid_y' => 'required|integer|min:0|max:7',
        ]);

        $generator = Generator::where('user_id', $user->id)->find($validated['generator_id']);
        if (!$generator) {
            return response()->json(['error' => 'Generator not found'], 404);
        }

        $generator->refreshCooldownIfExpired();

        $gx = $validated['grid_x'];
        $gy = $validated['grid_y'];

        if ($generator->grid_x === $gx && $generator->grid_y === $gy) {
            return response()->json([
                'success' => true,
                'generator' => $this->generatorToClientArray($generator->load('theme:id,slug,name,generator_image_url')),
            ]);
        }

        $itemOccupied = Item::where('user_id', $user->id)
            ->where('grid_x', $gx)
            ->where('grid_y', $gy)
            ->exists();

        if ($itemOccupied) {
            return response()->json(['error' => 'Cell occupied'], 422);
        }

        $generatorOccupied = Generator::where('user_id', $user->id)
            ->where('id', '!=', $generator->id)
            ->where('grid_x', $gx)
            ->where('grid_y', $gy)
            ->exists();

        if ($generatorOccupied) {
            return response()->json(['error' => 'Cell occupied'], 422);
        }

        $generator->update([
            'grid_x' => $gx,
            'grid_y' => $gy,
        ]);

        return response()->json([
            'success' => true,
            'generator' => $this->generatorToClientArray(
                $generator->fresh()->load('theme:id,slug,name,generator_image_url')
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generatorToClientArray(Generator $generator): array
    {
        $generator->loadMissing('theme:id,slug,name,generator_image_url');
        $data = $generator->toArray();
        $data['image_url'] = $generator->theme?->generator_image_path;

        return $data;
    }
}
