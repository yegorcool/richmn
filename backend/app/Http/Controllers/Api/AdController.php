<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\AdService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdController extends Controller
{
    use ResolvesUser;

    public function callback(Request $request, AdService $ads): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'format' => 'required|in:rewarded,interstitial,popup',
            'placement' => 'required|string',
        ]);

        if (!$ads->canShowAd($user, $validated['format'])) {
            return response()->json(['error' => 'Ad limit reached'], 422);
        }

        $ads->recordAdView($user, $validated['format'], $validated['placement']);

        return response()->json([
            'success' => true,
            'daily_count' => $ads->getDailyAdCount($user, $validated['format']),
        ]);
    }
}
