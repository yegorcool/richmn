<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CharacterLineService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ResolvesUser;

    public function index(Request $request, OrderService $orders): JsonResponse
    {
        $user = $this->user($request);
        $orders->ensureActiveOrders($user);

        return response()->json([
            'orders' => $user->activeOrders()->with('character')->get(),
        ]);
    }

    public function submit(Request $request, Order $order, OrderService $orders, CharacterLineService $cls): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'item_id' => 'required|integer',
        ]);

        $result = $orders->submitItem($user, $order, $validated['item_id']);

        if (!$result['success']) {
            return response()->json(['error' => $result['error']], 422);
        }

        $characterLine = null;
        $character = $order->character;

        if ($character) {
            $trigger = $result['partial'] ? 'order_partial' : 'order_complete';
            $extra = [];

            if (!$result['partial']) {
                $waitMinutes = $order->getWaitingMinutes();
                $extra['speed'] = $waitMinutes < 2 ? 'fast' : ($waitMinutes > 10 ? 'slow' : 'normal');
                $extra['order_level'] = max(array_column($order->required_items, 'item_level'));
            }

            $context = $cls->buildContext($user, $character, $order, $extra);
            $line = $cls->getLine($character, $trigger, $context, $user);

            if ($line) {
                $cls->recordShow($user, $line);
                $characterLine = ['id' => $line->id, 'character_id' => $line->character_id, 'text' => $line->text];
            }
        }

        return response()->json(array_merge($result, [
            'character_line' => $characterLine,
        ]));
    }
}
