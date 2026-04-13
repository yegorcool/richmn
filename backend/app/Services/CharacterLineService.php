<?php

namespace App\Services;

use App\Models\Character;
use App\Models\CharacterLine;
use App\Models\CharacterLineShow;
use App\Models\CharacterRelationship;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;

class CharacterLineService
{
    public function getLine(Character $character, string $trigger, array $context, User $user): ?CharacterLine
    {
        $candidates = CharacterLine::where('character_id', $character->id)
            ->where('trigger', $trigger)
            ->get();

        if ($candidates->isEmpty()) return null;

        $filtered = $candidates->filter(function (CharacterLine $line) use ($context, $user) {
            if (!$this->matchesConditions($line->conditions, $context)) {
                return false;
            }

            $show = CharacterLineShow::where('user_id', $user->id)
                ->where('character_line_id', $line->id)
                ->first();

            if ($show) {
                if ($show->shown_count >= $line->max_shows) return false;
                if ($line->cooldown_hours > 0 && $show->last_shown_at) {
                    $cooldownEnd = Carbon::parse($show->last_shown_at)->addHours($line->cooldown_hours);
                    if (now()->lt($cooldownEnd)) return false;
                }
            }

            return true;
        });

        if ($filtered->isEmpty()) {
            return $this->getFallback($character, $trigger, $user);
        }

        $maxPriority = $filtered->max('priority');
        $topPriority = $filtered->filter(fn($l) => $l->priority === $maxPriority);

        return $topPriority->random();
    }

    public function recordShow(User $user, CharacterLine $line): void
    {
        CharacterLineShow::updateOrCreate(
            ['user_id' => $user->id, 'character_line_id' => $line->id],
            ['last_shown_at' => now()]
        );

        CharacterLineShow::where('user_id', $user->id)
            ->where('character_line_id', $line->id)
            ->increment('shown_count');
    }

    public function getMood(Character $character, User $user, ?Order $order): string
    {
        if ($order) {
            $waitMinutes = $order->getWaitingMinutes();
            if ($waitMinutes > 5) return 'impatient';
        }

        $rel = $this->getRelationship($user, $character);
        $streak = $this->getOrderStreak($user);

        if ($streak >= 3 || $rel === 'loyal') return 'happy';

        return 'neutral';
    }

    public function getRelationship(User $user, Character $character): string
    {
        $rel = CharacterRelationship::where('user_id', $user->id)
            ->where('character_id', $character->id)
            ->first();

        return $rel?->relationship_level ?? 'new';
    }

    public function buildContext(User $user, Character $character, ?Order $order = null, array $extra = []): array
    {
        $relationship = $this->getRelationship($user, $character);
        $mood = $this->getMood($character, $user, $order);
        $streak = $this->getOrderStreak($user);

        $hour = (int) now()->format('H');
        $timeOfDay = match (true) {
            $hour >= 5 && $hour < 12 => 'morning',
            $hour >= 12 && $hour < 17 => 'afternoon',
            $hour >= 17 && $hour < 22 => 'evening',
            default => 'night',
        };

        $isFirstTime = !CharacterRelationship::where('user_id', $user->id)
            ->where('character_id', $character->id)
            ->exists();

        return array_merge([
            'relationship' => $relationship,
            'mood' => $mood,
            'streak' => $streak,
            'time_of_day' => $timeOfDay,
            'player_level' => $user->level,
            'first_time' => $isFirstTime,
        ], $extra);
    }

    private function matchesConditions(array $conditions, array $context): bool
    {
        foreach ($conditions as $key => $expected) {
            if ($expected === 'any' || $expected === null) continue;

            $actual = $context[$key] ?? null;
            if ($actual === null) continue;

            if (is_string($expected) && preg_match('/^(>=|<=|==|>|<)(\d+)$/', $expected, $matches)) {
                $op = $matches[1];
                $val = (int) $matches[2];
                $act = (int) $actual;
                $pass = match ($op) {
                    '>=' => $act >= $val,
                    '<=' => $act <= $val,
                    '==' => $act == $val,
                    '>' => $act > $val,
                    '<' => $act < $val,
                };
                if (!$pass) return false;
                continue;
            }

            if ($expected === 'low') {
                if (!in_array($actual, [1, 2, 3]) && $actual !== 'low') return false;
                continue;
            }
            if ($expected === 'mid') {
                if (!in_array($actual, [4, 5, 6, 7]) && $actual !== 'mid') return false;
                continue;
            }
            if ($expected === 'high') {
                if (!in_array($actual, [8, 9, 10]) && $actual !== 'high') return false;
                continue;
            }

            if (is_bool($expected)) {
                if ((bool) $actual !== $expected) return false;
                continue;
            }

            if ((string) $actual !== (string) $expected) return false;
        }

        return true;
    }

    private function getFallback(Character $character, string $trigger, User $user): ?CharacterLine
    {
        return CharacterLine::where('character_id', $character->id)
            ->where('trigger', $trigger)
            ->where('priority', '<=', 20)
            ->inRandomOrder()
            ->first()
            ?? CharacterLine::where('character_id', $character->id)
                ->where('trigger', $trigger)
                ->inRandomOrder()
                ->first();
    }

    private function getOrderStreak(User $user): int
    {
        return Order::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subMinutes(30))
            ->count();
    }
}
