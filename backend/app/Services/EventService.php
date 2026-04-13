<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class EventService
{
    public function getActiveEvents(): Collection
    {
        return Event::where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->get();
    }

    public function getProgressForUser(User $user, Event $event): EventProgress
    {
        return EventProgress::firstOrCreate(
            ['user_id' => $user->id, 'event_id' => $event->id],
            ['score' => 0, 'milestones_claimed' => []]
        );
    }

    public function addScore(User $user, Event $event, int $points): EventProgress
    {
        $progress = $this->getProgressForUser($user, $event);
        $progress->increment('score', $points);

        return $progress->fresh();
    }

    public function claimMilestone(User $user, Event $event, int $milestoneIndex): array
    {
        $progress = $this->getProgressForUser($user, $event);
        $milestones = $event->config['milestones'] ?? [];

        if (!isset($milestones[$milestoneIndex])) {
            return ['success' => false, 'error' => 'Invalid milestone'];
        }

        $milestone = $milestones[$milestoneIndex];
        if ($progress->score < $milestone['threshold']) {
            return ['success' => false, 'error' => 'Score too low'];
        }

        $claimed = $progress->milestones_claimed ?? [];
        if (in_array($milestoneIndex, $claimed)) {
            return ['success' => false, 'error' => 'Already claimed'];
        }

        $claimed[] = $milestoneIndex;
        $progress->update(['milestones_claimed' => $claimed]);

        return ['success' => true, 'reward' => $milestone['reward'] ?? []];
    }

    public function getLeaderboard(Event $event, int $limit = 50): Collection
    {
        return EventProgress::where('event_id', $event->id)
            ->orderByDesc('score')
            ->limit($limit)
            ->with('user:id,first_name,username,avatar_url')
            ->get();
    }
}
