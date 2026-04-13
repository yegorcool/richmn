<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Services\DailyChallengeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyChallengeController extends Controller
{
    use ResolvesUser;

    public function show(Request $request, DailyChallengeService $challenges): JsonResponse
    {
        $user = $this->user($request);
        $daily = $challenges->getOrCreateToday($user);

        return response()->json([
            'date' => $daily->date,
            'challenges' => $daily->challenges,
            'completed' => $daily->completed ?? [],
        ]);
    }

    public function claim(Request $request, int $challenge, DailyChallengeService $challenges): JsonResponse
    {
        $user = $this->user($request);
        $result = $challenges->claimReward($user, $challenge);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        return response()->json($result);
    }
}
