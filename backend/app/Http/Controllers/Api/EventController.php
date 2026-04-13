<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ResolvesUser;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    use ResolvesUser;

    public function active(Request $request, EventService $events): JsonResponse
    {
        $user = $this->user($request);
        $activeEvents = $events->getActiveEvents();

        $result = $activeEvents->map(function (Event $event) use ($user, $events) {
            $progress = $events->getProgressForUser($user, $event);
            return [
                'id' => $event->id,
                'type' => $event->type,
                'name' => $event->name,
                'starts_at' => $event->starts_at,
                'ends_at' => $event->ends_at,
                'score' => $progress->score,
                'milestones' => $event->config['milestones'] ?? [],
                'milestones_claimed' => $progress->milestones_claimed ?? [],
            ];
        });

        return response()->json(['events' => $result]);
    }

    public function progress(Request $request, Event $event, EventService $events): JsonResponse
    {
        $user = $this->user($request);
        $progress = $events->getProgressForUser($user, $event);

        return response()->json([
            'event' => $event,
            'progress' => $progress,
        ]);
    }

    public function leaderboard(Request $request, Event $event, EventService $events): JsonResponse
    {
        return response()->json([
            'leaderboard' => $events->getLeaderboard($event),
        ]);
    }
}
