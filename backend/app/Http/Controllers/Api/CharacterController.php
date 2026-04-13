<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Services\CharacterLineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    use ResolvesUser;

    public function index(Request $request): JsonResponse
    {
        $user = $this->user($request);

        $characters = Character::where('is_active', true)
            ->where('unlock_level', '<=', $user->level)
            ->get();

        return response()->json(['characters' => $characters]);
    }

    public function line(Request $request, Character $character, CharacterLineService $cls): JsonResponse
    {
        $user = $this->user($request);

        $trigger = $request->query('trigger', 'order_appear');
        $extra = $request->query('context', []);
        if (is_string($extra)) {
            $extra = json_decode($extra, true) ?? [];
        }

        $context = $cls->buildContext($user, $character, null, $extra);
        $line = $cls->getLine($character, $trigger, $context, $user);

        if (!$line) {
            return response()->json(['line' => null]);
        }

        $cls->recordShow($user, $line);

        return response()->json([
            'line' => [
                'id' => $line->id,
                'character_id' => $line->character_id,
                'trigger' => $line->trigger,
                'text' => $line->text,
            ],
            'mood' => $cls->getMood($character, $user, null),
            'relationship' => $cls->getRelationship($user, $character),
        ]);
    }
}
