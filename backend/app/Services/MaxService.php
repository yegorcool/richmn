<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MaxService
{
    public function sendMessage(string $userId, string $text): bool
    {
        $apiUrl = config('max.api_url');
        if (empty($apiUrl)) {
            Log::warning('MAX API URL not configured');
            return false;
        }

        try {
            $response = Http::post("{$apiUrl}/messages", [
                'user_id' => $userId,
                'text' => $text,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('MAX sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEnergyFullNotification(string $userId): bool
    {
        return $this->sendMessage($userId, '⚡ Заряды полны! Самое время продолжить игру!');
    }

    public function sendChestReadyNotification(string $userId): bool
    {
        return $this->sendMessage($userId, '🎁 Твой сундук открылся! Забери награду!');
    }
}
