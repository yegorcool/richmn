<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('notifications:send')->everyMinute();
Schedule::command('energy:check-notifications')->everyFiveMinutes();
Schedule::command('events:manage')->daily();
Schedule::command('daily-challenge:rotate')->dailyAt('00:00');
Schedule::command('streaks:warn')->dailyAt('20:00');
