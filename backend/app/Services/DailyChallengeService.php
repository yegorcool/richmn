<?php

namespace App\Services;

use App\Models\DailyChallenge;
use App\Models\User;

class DailyChallengeService
{
    private const CHALLENGE_TEMPLATES = [
        'easy' => [
            ['desc' => 'Выполни {n} заказов', 'key' => 'orders_completed', 'min' => 3, 'max' => 5],
            ['desc' => 'Сделай {n} merge', 'key' => 'merges', 'min' => 15, 'max' => 25],
            ['desc' => 'Тапни генератор {n} раз', 'key' => 'generator_taps', 'min' => 5, 'max' => 10],
        ],
        'medium' => [
            ['desc' => 'Выполни {n} заказов', 'key' => 'orders_completed', 'min' => 7, 'max' => 10],
            ['desc' => 'Сделай {n} merge', 'key' => 'merges', 'min' => 30, 'max' => 50],
            ['desc' => 'Получи предмет {n} уровня', 'key' => 'max_item_level', 'min' => 5, 'max' => 7],
        ],
        'hard' => [
            ['desc' => 'Выполни {n} заказов', 'key' => 'orders_completed', 'min' => 12, 'max' => 15],
            ['desc' => 'Сделай {n} chain merge', 'key' => 'chain_merges', 'min' => 3, 'max' => 5],
            ['desc' => 'Получи предмет {n} уровня', 'key' => 'max_item_level', 'min' => 7, 'max' => 9],
        ],
    ];

    private const REWARDS = [
        'easy' => ['energy' => 5],
        'medium' => ['energy' => 10, 'chest' => 'small'],
        'hard' => ['energy' => 20],
    ];

    public function getOrCreateToday(User $user): DailyChallenge
    {
        $today = now()->toDateString();

        return DailyChallenge::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['challenges' => $this->generateChallenges(), 'completed' => []]
        );
    }

    public function claimReward(User $user, int $challengeIndex): array
    {
        $daily = $this->getOrCreateToday($user);
        $challenges = $daily->challenges;

        if (!isset($challenges[$challengeIndex])) {
            return ['success' => false, 'error' => 'Invalid challenge'];
        }

        $challenge = $challenges[$challengeIndex];
        $completed = $daily->completed ?? [];

        if (in_array($challengeIndex, $completed)) {
            return ['success' => false, 'error' => 'Already claimed'];
        }

        if (($challenge['progress'] ?? 0) < $challenge['target']) {
            return ['success' => false, 'error' => 'Challenge not completed'];
        }

        $completed[] = $challengeIndex;
        $daily->update(['completed' => $completed]);

        $reward = self::REWARDS[$challenge['difficulty']] ?? [];

        if (isset($reward['energy'])) {
            app(EnergyService::class)->refillFromBonus($user, 'daily_challenge', $reward['energy']);
        }
        if (isset($reward['chest'])) {
            app(ChestService::class)->createChest($user, $reward['chest'], 'daily_challenge');
        }

        $allCompleted = count($completed) >= count($challenges);
        if ($allCompleted) {
            app(ChestService::class)->createChest($user, 'super', 'daily_challenge_bonus');
        }

        return [
            'success' => true,
            'reward' => $reward,
            'all_completed' => $allCompleted,
        ];
    }

    private function generateChallenges(): array
    {
        $challenges = [];
        foreach (['easy', 'medium', 'hard'] as $difficulty) {
            $templates = self::CHALLENGE_TEMPLATES[$difficulty];
            $template = $templates[array_rand($templates)];
            $target = rand($template['min'], $template['max']);

            $challenges[] = [
                'difficulty' => $difficulty,
                'description' => str_replace('{n}', (string) $target, $template['desc']),
                'key' => $template['key'],
                'target' => $target,
                'progress' => 0,
            ];
        }

        return $challenges;
    }
}
