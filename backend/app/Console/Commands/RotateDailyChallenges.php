<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DailyChallengeService;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class RotateDailyChallenges extends Command
{
    protected $signature = 'daily-challenge:rotate';
    protected $description = 'Generate new daily challenges and notify active users';

    public function handle(DailyChallengeService $challenges, NotificationService $notifications): int
    {
        $activeUsers = User::where('last_activity', '>=', now()->subDays(3))
            ->limit(1000)
            ->get();

        $count = 0;
        foreach ($activeUsers as $user) {
            $challenges->getOrCreateToday($user);
            $notifications->queueNotification($user, 'daily_challenge', '📋 Новые задания дня! Заходи за наградами!');
            $count++;
        }

        $this->info("Created challenges for {$count} users");
        return self::SUCCESS;
    }
}
