<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationService
{
    public function __construct(
        private TelegramService $telegram,
        private MaxService $max,
    ) {}

    public function queueNotification(User $user, string $type, string $message): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'channel' => $user->source,
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function processQueue(int $batchSize = 100): int
    {
        $sent = 0;

        $notifications = Notification::where('status', 'pending')
            ->orderBy('created_at')
            ->limit($batchSize)
            ->with('user')
            ->get();

        foreach ($notifications as $notification) {
            $user = $notification->user;
            if (!$user) continue;

            $todaySentCount = Notification::where('user_id', $user->id)
                ->where('status', 'sent')
                ->where('sent_at', '>=', Carbon::today())
                ->count();

            if ($todaySentCount >= 3) {
                continue;
            }

            $success = $this->send($notification);
            $notification->update([
                'status' => $success ? 'sent' : 'failed',
                'sent_at' => $success ? now() : null,
            ]);

            if ($success) $sent++;
        }

        return $sent;
    }

    private function send(Notification $notification): bool
    {
        $user = $notification->user;

        return match ($notification->channel) {
            'telegram' => $this->telegram->sendMessage($user->platform_id, $notification->message),
            'max' => $this->max->sendMessage($user->platform_id, $notification->message),
            default => false,
        };
    }
}
