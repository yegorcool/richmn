<?php

return [
    'energy' => [
        'max' => (int) env('ENERGY_MAX', 50),
        'recovery_minutes' => (int) env('ENERGY_RECOVERY_MINUTES', 3),
        'ad_refill' => (int) env('ENERGY_AD_REFILL', 10),
        'ad_daily_limit' => (int) env('ENERGY_AD_DAILY_LIMIT', 5),
    ],

    'orders' => [
        'respawn_seconds' => (int) env('ORDER_RESPAWN_SECONDS', 30),
        'max_active' => (int) env('MAX_ACTIVE_ORDERS', 3),
    ],

    'grid' => [
        'width' => 6,
        'height' => 8,
    ],

    'ads' => [
        'rewarded_daily_limit' => (int) env('AD_REWARDED_DAILY_LIMIT', 12),
        'interstitial_min_interval' => (int) env('AD_INTERSTITIAL_MIN_INTERVAL', 180),
        'popup_daily_limit' => (int) env('AD_POPUP_DAILY_LIMIT', 3),
    ],

    'chest_timers' => [
        'small' => 15 * 60,
        'medium' => 60 * 60,
        'large' => 4 * 60 * 60,
        'super' => 0,
    ],

    'generator' => [
        'default_limit' => (int) env('GENERATOR_DEFAULT_LIMIT', 5),
        'default_timeout' => (int) env('GENERATOR_DEFAULT_TIMEOUT', 1800),
        'default_energy_cost' => (int) env('GENERATOR_ENERGY_COST', 1),
    ],

    'relationship_thresholds' => [
        'new' => 0,
        'familiar' => 6,
        'loyal' => 31,
    ],
];
