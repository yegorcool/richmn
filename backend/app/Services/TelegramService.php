<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $apiUrl;
    private string $botToken;

    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->apiUrl = config('telegram.api_url') . $this->botToken;
    }

    public function sendMessage(string $chatId, string $text, array $options = []): bool
    {
        if (empty($this->botToken)) {
            Log::warning('Telegram bot token not configured');
            return false;
        }

        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options));

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
            return false;
        }
    }

    public function sendEnergyFullNotification(string $chatId): bool
    {
        return $this->sendMessage($chatId, '⚡ Заряды полны! Самое время продолжить игру!');
    }

    public function sendChestReadyNotification(string $chatId): bool
    {
        return $this->sendMessage($chatId, '🎁 Твой сундук открылся! Забери награду!');
    }

    public function sendGeneratorReadyNotification(string $chatId, string $generatorName): bool
    {
        return $this->sendMessage($chatId, "⚙️ {$generatorName} готов! Новые предметы ждут!");
    }

    public function sendDailyChallengeNotification(string $chatId): bool
    {
        return $this->sendMessage($chatId, '📋 Новые задания дня! Заходи за наградами!');
    }

    public function sendStreakWarningNotification(string $chatId, int $streakDays): bool
    {
        return $this->sendMessage($chatId, "🔥 Не забудь зайти! Твой streak уже {$streakDays} дней!");
    }

    public function sendEventNotification(string $chatId, string $eventName): bool
    {
        return $this->sendMessage($chatId, "🎉 Начался ивент \"{$eventName}\"! Заходи играть!");
    }

    /**
     * Validates Telegram Login Widget payload (browser), per
     * https://core.telegram.org/widgets/login — secret is SHA256(bot_token), not WebAppData.
     *
     * @param  array<string, string>  $authData
     * @return array<string, mixed>|null Normalized user fields for User::firstOrCreate
     */
    public function validateLoginWidgetData(array $authData): ?array
    {
        if (empty($this->botToken)) {
            return null;
        }

        if (!isset($authData['hash'])) {
            return null;
        }

        $hash = $authData['hash'];
        $check = $authData;
        unset($check['hash']);

        $dataCheckString = collect($check)
            ->sortKeys()
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash('sha256', $this->botToken, true);
        $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        if (!hash_equals($expectedHash, $hash)) {
            return null;
        }

        if (isset($authData['auth_date'])) {
            $authDate = (int) $authData['auth_date'];
            if (time() - $authDate > 86400) {
                return null;
            }
        }

        return [
            'id' => (int) $authData['id'],
            'username' => $authData['username'] ?? null,
            'first_name' => $authData['first_name'] ?? 'Player',
            'last_name' => $authData['last_name'] ?? null,
            'photo_url' => $authData['photo_url'] ?? null,
            'is_premium' => false,
            'language_code' => 'ru',
        ];
    }
}
