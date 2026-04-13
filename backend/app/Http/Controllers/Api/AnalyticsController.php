<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    use ResolvesUser;

    public function track(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $validated = $request->validate([
            'events' => 'required|array|max:50',
            'events.*.name' => 'required|string|max:100',
            'events.*.properties' => 'nullable|array',
            'events.*.timestamp' => 'nullable|integer',
        ]);

        $records = [];
        foreach ($validated['events'] as $event) {
            $records[] = [
                'user_id' => $user->id,
                'event_name' => $event['name'],
                'properties' => json_encode($event['properties'] ?? []),
                'occurred_at' => isset($event['timestamp'])
                    ? \Carbon\Carbon::createFromTimestamp($event['timestamp'])
                    : now(),
            ];
        }

        AnalyticsEvent::insert($records);

        return response()->json(['success' => true, 'tracked' => count($records)]);
    }
}
