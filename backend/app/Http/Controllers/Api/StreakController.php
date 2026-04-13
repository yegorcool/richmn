<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StreakController extends Controller
{
    use ResolvesUser;

    public function show(Request $request, StreakService $streaks): JsonResponse
    {
        $user = $this->user($request);
        return response()->json($streaks->getStreakInfo($user));
    }

    public function claim(Request $request, StreakService $streaks): JsonResponse
    {
        $user = $this->user($request);
        $result = $streaks->claimReward($user);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json($result);
    }
}
