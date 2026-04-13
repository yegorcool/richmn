<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckEnergyNotifications extends Command
{
    protected $signature = 'energy:check-notifications';
    protected $description = 'Send notifications to users whose energy is full';

    public function handle(NotificationService $notifications): int
    {
        $maxEnergy = config('game.energy.max');
        $recoveryMinutes = config('game.energy.recovery_minutes');
        $fullRecoveryMinutes = $maxEnergy * $recoveryMinutes;

        $users = User::where('energy', '<', $maxEnergy)
            ->where('energy_updated_at', '<=', now()->subMinutes($fullRecoveryMinutes))
            ->where('last_activity', '>=', now()->subDays(7))
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $notifications->queueNotification($user, 'energy_full', '⚡ Заряды полны! Самое время продолжить!');
            $user->update(['energy' => $maxEnergy, 'energy_updated_at' => now()]);
            $count++;
        }

        $this->info("Queued {$count} energy notifications");
        return self::SUCCESS;
    }
}
