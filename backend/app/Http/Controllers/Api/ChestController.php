<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Chest;
use App\Services\AdService;
use App\Services\ChestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChestController extends Controller
{
    use ResolvesUser;

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $chests = Chest::where('user_id', $user->id)
            ->whereNull('opened_at')
            ->orderBy('created_at')
            ->get()
            ->map(fn(Chest $c) => [
                'id' => $c->id,
                'type' => $c->type,
                'source' => $c->source,
                'unlock_at' => $c->unlock_at,
                'can_open' => $c->canOpen(),
            ]);

        return response()->json(['chests' => $chests]);
    }

    public function open(Request $request, Chest $chest, ChestService $chests, AdService $ads): JsonResponse
    {
        $user = $this->user($request);
        $adSkip = $request->boolean('ad_skip', false);

        if ($adSkip) {
            if (!$ads->canShowAd($user, 'rewarded')) {
                return response()->json(['error' => 'Ad limit reached'], 422);
            }
            $ads->recordAdView($user, 'rewarded', 'chest_open');
        }

        $result = $chests->openChest($user, $chest, $adSkip);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json($result);
    }
}
