<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\User;
use App\Services\EnergyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    use ResolvesUser;

    public function show(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $invitedCount = Referral::where('referrer_id', $user->id)->count();
        $platform = $request->attributes->get('platform', 'telegram');

        $link = $platform === 'telegram'
            ? "https://t.me/" . config('telegram.bot_username') . "?start={$user->referral_code}"
            : "https://max.richmn.com/?ref={$user->referral_code}";

        return response()->json([
            'referral_code' => $user->referral_code,
            'invited_count' => $invitedCount,
            'link' => $link,
            'reward_per_invite' => 20,
        ]);
    }

    public function claim(Request $request, EnergyService $energy): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'referral_code' => 'required|string',
        ]);

        if ($user->referred_by) {
            return response()->json(['error' => 'Already referred'], 422);
        }

        $referrer = User::where('referral_code', $validated['referral_code'])->first();
        if (!$referrer || $referrer->id === $user->id) {
            return response()->json(['error' => 'Invalid referral code'], 422);
        }

        $user->update(['referred_by' => $referrer->id]);
        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $user->id,
            'rewarded' => true,
        ]);

        $energy->refillFromBonus($user, 'referral_new', 20);
        $energy->refillFromBonus($referrer, 'referral_invite', 20);

        return response()->json(['success' => true, 'energy_bonus' => 20]);
    }
}
