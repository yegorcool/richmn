<?php

namespace App\Services;

use App\Models\Streak;
use App\Models\User;

class StreakService
{
    private const REWARDS = [
        1 => ['energy' => 10],
        2 => ['chest' => 'small'],
        3 => ['energy' => 15],
        4 => ['chest' => 'medium'],
        5 => ['energy' => 20, 'generator_boost' => true],
        6 => ['chest' => 'large'],
        7 => ['energy' => 50, 'exclusive_decor' => true],
    ];

    public function getOrCreateStreak(User $user): Streak
    {
        $streak = Streak::firstOrCreate(
            ['user_id' => $user->id],
            ['current_streak' => 0, 'longest_streak' => 0, 'reward_claimed_today' => false]
        );

        $streak->checkIn();
        return $streak->fresh();
    }

    public function claimReward(User $user): array
    {
        $streak = $this->getOrCreateStreak($user);

        if ($streak->reward_claimed_today) {
            return ['success' => false, 'error' => 'Already claimed today'];
        }

        $day = $streak->getStreakDay();
        $reward = self::REWARDS[$day] ?? self::REWARDS[1];

        $streak->update(['reward_claimed_today' => true]);

        if (isset($reward['energy'])) {
            app(EnergyService::class)->refillFromBonus($user, 'streak_day_' . $day, $reward['energy']);
        }

        if (isset($reward['chest'])) {
            app(ChestService::class)->createChest($user, $reward['chest'], 'streak');
        }

        return ['success' => true, 'reward' => $reward, 'day' => $day];
    }

    public function getStreakInfo(User $user): array
    {
        $streak = $this->getOrCreateStreak($user);

        $rewards = [];
        for ($day = 1; $day <= 7; $day++) {
            $rewards[] = [
                'day' => $day,
                'reward' => self::REWARDS[$day] ?? [],
                'claimed' => $day < $streak->getStreakDay() || ($day === $streak->getStreakDay() && $streak->reward_claimed_today),
                'current' => $day === $streak->getStreakDay(),
            ];
        }

        return [
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'reward_claimed_today' => $streak->reward_claimed_today,
            'rewards' => $rewards,
        ];
    }
}
