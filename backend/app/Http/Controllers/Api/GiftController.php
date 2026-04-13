<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Gift;
use App\Models\User;
use App\Services\EnergyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    use ResolvesUser;

    public function send(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'receiver_id' => 'required|integer',
        ]);

        $receiver = User::find($validated['receiver_id']);
        if (!$receiver || $receiver->id === $user->id) {
            return response()->json(['error' => 'Invalid receiver'], 422);
        }

        $todaySent = Gift::where('sender_id', $user->id)
            ->where('receiver_id', $receiver->id)
            ->whereDate('created_at', today())
            ->exists();

        if ($todaySent) {
            return response()->json(['error' => 'Already sent today'], 422);
        }

        Gift::create([
            'sender_id' => $user->id,
            'receiver_id' => $receiver->id,
            'energy_amount' => 5,
        ]);

        return response()->json(['success' => true]);
    }

    public function claimAll(Request $request, EnergyService $energy): JsonResponse
    {
        $user = $this->user($request);

        $unclaimed = Gift::where('receiver_id', $user->id)
            ->where('claimed', false)
            ->get();

        if ($unclaimed->isEmpty()) {
            return response()->json(['claimed' => 0]);
        }

        $totalEnergy = $unclaimed->sum('energy_amount');
        $energy->refillFromBonus($user, 'gift', $totalEnergy);

        Gift::whereIn('id', $unclaimed->pluck('id'))->update(['claimed' => true]);

        return response()->json([
            'claimed' => $unclaimed->count(),
            'energy_received' => $totalEnergy,
        ]);
    }
}
