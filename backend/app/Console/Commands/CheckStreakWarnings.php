<?php

namespace App\Console\Commands;

use App\Models\Streak;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class CheckStreakWarnings extends Command
{
    protected $signature = 'streaks:warn';
    protected $description = 'Send streak warning notifications to users who haven\'t logged in today';

    public function handle(NotificationService $notifications): int
    {
        $streaks = Streak::where('current_streak', '>=', 2)
            ->where('last_login_date', '<', today())
            ->with('user')
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($streaks as $streak) {
            if (!$streak->user) continue;
            $notifications->queueNotification(
                $streak->user,
                'streak_warning',
                "🔥 Не забудь зайти! Твой streak уже {$streak->current_streak} дней!"
            );
            $count++;
        }

        $this->info("Sent {$count} streak warnings");
        return self::SUCCESS;
    }
}
