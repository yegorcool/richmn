<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class ProcessNotifications extends Command
{
    protected $signature = 'notifications:send {--batch=100}';
    protected $description = 'Process pending notification queue';

    public function handle(NotificationService $service): int
    {
        $sent = $service->processQueue((int) $this->option('batch'));
        $this->info("Sent {$sent} notifications");
        return self::SUCCESS;
    }
}
