<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\AdService;
use App\Services\EnergyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnergyController extends Controller
{
    use ResolvesUser;

    public function show(Request $request, EnergyService $energy): JsonResponse
    {
        $user = $this->user($request);

        return response()->json([
            'energy' => $energy->getCurrentEnergy($user),
            'max' => config('game.energy.max'),
            'recovery_seconds' => $energy->getRecoverySecondsRemaining($user),
            'ad_refills_remaining' => config('game.energy.ad_daily_limit') - app(AdService::class)->getDailyAdCount($user, 'rewarded'),
        ]);
    }

    public function refill(Request $request, EnergyService $energy, AdService $ads): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'source' => 'required|in:ad,referral',
        ]);

        if ($validated['source'] === 'ad') {
            if (!$ads->canShowAd($user, 'rewarded')) {
                return response()->json(['error' => 'Daily ad limit reached'], 422);
            }

            $ads->recordAdView($user, 'rewarded', 'energy_refill');
            $newEnergy = $energy->refillFromAd($user);

            return response()->json([
                'energy' => $newEnergy,
                'max' => config('game.energy.max'),
            ]);
        }

        if ($validated['source'] === 'referral') {
            $newEnergy = $energy->refillFromBonus($user, 'referral', 20);
            return response()->json([
                'energy' => $newEnergy,
                'max' => config('game.energy.max'),
            ]);
        }

        return response()->json(['error' => 'Invalid source'], 422);
    }
}
