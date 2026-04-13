<?php

namespace App\Services;

use App\Models\EnergyLog;
use App\Models\User;
use Carbon\Carbon;

class EnergyService
{
    public function getCurrentEnergy(User $user): int
    {
        $max = config('game.energy.max');
        $recoveryMinutes = config('game.energy.recovery_minutes');

        if ($user->energy >= $max) {
            return $max;
        }

        $minutesSinceUpdate = $user->energy_updated_at
            ? Carbon::parse($user->energy_updated_at)->diffInMinutes(now())
            : 0;

        $recovered = intdiv((int) $minutesSinceUpdate, $recoveryMinutes);
        $currentEnergy = min($user->energy + $recovered, $max);

        if ($recovered > 0) {
            $user->update([
                'energy' => $currentEnergy,
                'energy_updated_at' => now(),
            ]);
        }

        return $currentEnergy;
    }

    public function spendEnergy(User $user, int $amount = 1, string $source = 'merge'): bool
    {
        $current = $this->getCurrentEnergy($user);

        if ($current < $amount) {
            return false;
        }

        $user->update([
            'energy' => $current - $amount,
            'energy_updated_at' => now(),
        ]);

        EnergyLog::create([
            'user_id' => $user->id,
            'type' => 'spent',
            'amount' => -$amount,
            'source' => $source,
        ]);

        return true;
    }

    public function refillFromAd(User $user): int
    {
        $refillAmount = config('game.energy.ad_refill');
        $max = config('game.energy.max');
        $current = $this->getCurrentEnergy($user);
        $newEnergy = min($current + $refillAmount, $max);
        $actual = $newEnergy - $current;

        $user->update([
            'energy' => $newEnergy,
            'energy_updated_at' => now(),
        ]);

        EnergyLog::create([
            'user_id' => $user->id,
            'type' => 'rewarded',
            'amount' => $actual,
            'source' => 'ad',
        ]);

        return $newEnergy;
    }

    public function refillFromBonus(User $user, string $source, int $amount): int
    {
        $max = config('game.energy.max');
        $current = $this->getCurrentEnergy($user);
        $newEnergy = min($current + $amount, $max);
        $actual = $newEnergy - $current;

        $user->update([
            'energy' => $newEnergy,
            'energy_updated_at' => now(),
        ]);

        EnergyLog::create([
            'user_id' => $user->id,
            'type' => 'bonus',
            'amount' => $actual,
            'source' => $source,
        ]);

        return $newEnergy;
    }

    public function getRecoverySecondsRemaining(User $user): int
    {
        $max = config('game.energy.max');
        $current = $this->getCurrentEnergy($user);

        if ($current >= $max) {
            return 0;
        }

        $recoverySeconds = config('game.energy.recovery_minutes') * 60;
        $secondsSinceUpdate = $user->energy_updated_at
            ? Carbon::parse($user->energy_updated_at)->diffInSeconds(now())
            : 0;

        $secondsIntoCurrentRecovery = $secondsSinceUpdate % $recoverySeconds;
        return $recoverySeconds - (int) $secondsIntoCurrentRecovery;
    }
}
