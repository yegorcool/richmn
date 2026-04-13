<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\EnergyService;
use App\Services\OrderService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ResolvesUser;

    public function me(Request $request, EnergyService $energy, OrderService $orders, StreakService $streaks): JsonResponse
    {
        $user = $this->user($request);
        $currentEnergy = $energy->getCurrentEnergy($user);

        $orders->ensureActiveOrders($user);
        $streaks->getOrCreateStreak($user);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'avatar_url' => $user->avatar_url,
                'level' => $user->level,
                'experience' => $user->experience,
                'energy' => $currentEnergy,
                'energy_max' => config('game.energy.max'),
                'energy_recovery_seconds' => $energy->getRecoverySecondsRemaining($user),
                'coins' => $user->coins,
                'referral_code' => $user->referral_code,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'language_code' => 'sometimes|string|max:10',
        ]);

        $user->update($validated);
        return response()->json(['success' => true]);
    }
}
