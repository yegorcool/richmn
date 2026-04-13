<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\DecorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DecorController extends Controller
{
    use ResolvesUser;

    public function locations(Request $request, DecorService $decor): JsonResponse
    {
        $user = $this->user($request);
        return response()->json(['locations' => $decor->getLocationsForUser($user)]);
    }

    public function place(Request $request, DecorService $decor): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'location_id' => 'required|integer',
            'item_key' => 'required|string',
            'style_variant' => 'required|integer|min:1|max:3',
        ]);

        $result = $decor->placeDecor($user, $validated['location_id'], $validated['item_key'], $validated['style_variant']);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json($result);
    }

    public function remove(Request $request, DecorService $decor): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'location_id' => 'required|integer',
            'item_key' => 'required|string',
        ]);

        $result = $decor->removeDecor($user, $validated['location_id'], $validated['item_key']);
        return response()->json($result);
    }
}
