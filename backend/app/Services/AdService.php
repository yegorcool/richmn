<?php

namespace App\Services;

use App\Models\AdView;
use App\Models\User;
use Carbon\Carbon;

class AdService
{
    public function canShowAd(User $user, string $format): bool
    {
        $today = Carbon::today();

        if ($format === 'rewarded' || $format === 'popup') {
            $dailyCount = AdView::where('user_id', $user->id)
                ->where('format', $format)
                ->where('viewed_at', '>=', $today)
                ->count();

            $limit = $format === 'rewarded'
                ? config('game.ads.rewarded_daily_limit')
                : config('game.ads.popup_daily_limit');

            return $dailyCount < $limit;
        }

        if ($format === 'interstitial') {
            $lastView = AdView::where('user_id', $user->id)
                ->where('format', 'interstitial')
                ->orderByDesc('viewed_at')
                ->first();

            if (!$lastView) return true;

            $minInterval = config('game.ads.interstitial_min_interval');
            return Carbon::parse($lastView->viewed_at)->addSeconds($minInterval)->isPast();
        }

        return false;
    }

    public function recordAdView(User $user, string $format, string $placement): AdView
    {
        return AdView::create([
            'user_id' => $user->id,
            'format' => $format,
            'placement' => $placement,
            'viewed_at' => now(),
        ]);
    }

    public function getDailyAdCount(User $user, string $format): int
    {
        return AdView::where('user_id', $user->id)
            ->where('format', $format)
            ->where('viewed_at', '>=', Carbon::today())
            ->count();
    }
}
