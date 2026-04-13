<?php

namespace App\Console\Commands;

use App\Models\Event;
use Illuminate\Console\Command;

class ManageEvents extends Command
{
    protected $signature = 'events:manage';
    protected $description = 'Start and end events based on schedule';

    public function handle(): int
    {
        $started = Event::where('is_active', false)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->update(['is_active' => true]);

        $ended = Event::where('is_active', true)
            ->where('ends_at', '<', now())
            ->update(['is_active' => false]);

        $this->info("Started {$started} events, ended {$ended} events");
        return self::SUCCESS;
    }
}
