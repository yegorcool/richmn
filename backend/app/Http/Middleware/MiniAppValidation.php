<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\GameInitService;
use App\Services\TelegramService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MiniAppValidation
{
    public function __construct(
        private TelegramService $telegramService,
        private GameInitService $gameInit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $platform = $request->header('X-Platform', 'telegram');

        $userData = match ($platform) {
            'telegram' => $this->resolveTelegramUser($request),
            'max' => $this->resolveMaxUser($request),
            default => null,
        };

        if ($userData === null) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $user = User::firstOrCreate(
            [
                'platform_id' => (string) $userData['id'],
                'source' => $platform,
            ],
            [
                'username' => $userData['username'] ?? null,
                'first_name' => $userData['first_name'] ?? 'Player',
                'last_name' => $userData['last_name'] ?? null,
                'avatar_url' => $userData['photo_url'] ?? null,
                'is_premium' => $userData['is_premium'] ?? false,
                'language_code' => $userData['language_code'] ?? 'ru',
                'energy' => config('game.energy.max'),
                'energy_updated_at' => now(),
                'referral_code' => User::generateReferralCode(),
            ]
        );

        if ($user->wasRecentlyCreated) {
            $this->gameInit->seedStarterGenerators($user);
        }

        $user->update(['last_activity' => now()]);

        $request->attributes->set('user', $user);
        $request->attributes->set('platform', $platform);

        return $next($request);
    }

    private function resolveTelegramUser(Request $request): ?array
    {
        $initData = $request->header('X-Platform-Init-Data', '');
        if ($initData !== '') {
            $fromMiniApp = $this->validateTelegramInitData($initData);
            if ($fromMiniApp !== null) {
                return $fromMiniApp;
            }
        }

        $widgetPayload = $this->extractTelegramLoginWidgetPayload($request);
        if ($widgetPayload === null) {
            return null;
        }

        return $this->telegramService->validateLoginWidgetData($widgetPayload);
    }

    private function resolveMaxUser(Request $request): ?array
    {
        $initData = $request->header('X-Platform-Init-Data', '');
        if ($initData === '') {
            return null;
        }

        return $this->validateMax($initData);
    }

    /**
     * @return array<string, string>|null
     */
    private function extractTelegramLoginWidgetPayload(Request $request): ?array
    {
        $fields = ['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date', 'hash'];
        $authData = [];
        foreach ($fields as $field) {
            $value = $request->input($field);
            if ($value !== null && $value !== '') {
                $authData[$field] = (string) $value;
            }
        }

        foreach (['id', 'auth_date', 'hash'] as $required) {
            if (!isset($authData[$required])) {
                return null;
            }
        }

        return $authData;
    }

    private function validateTelegramInitData(string $initData): ?array
    {
        $botToken = config('telegram.bot_token');
        if (empty($botToken)) {
            return $this->parseInitDataUnsafe($initData);
        }

        $parsed = [];
        parse_str($initData, $parsed);

        if (empty($parsed['hash'])) {
            return null;
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);

        ksort($parsed);
        $dataCheckString = collect($parsed)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        if (!hash_equals($calculatedHash, $hash)) {
            return null;
        }

        $userData = json_decode($parsed['user'] ?? '{}', true);
        return is_array($userData) ? $userData : null;
    }

    private function validateMax(string $initData): ?array
    {
        $appSecret = config('max.app_secret');
        if (empty($appSecret)) {
            return $this->parseInitDataUnsafe($initData);
        }

        $parsed = [];
        parse_str($initData, $parsed);

        if (empty($parsed['hash'])) {
            return null;
        }

        $hash = $parsed['hash'];
        unset($parsed['hash']);

        ksort($parsed);
        $dataCheckString = collect($parsed)
            ->map(fn($value, $key) => "{$key}={$value}")
            ->implode("\n");

        $secretKey = hash_hmac('sha256', $appSecret, 'WebAppData', true);
        $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        if (!hash_equals($calculatedHash, $hash)) {
            return null;
        }

        $userData = json_decode($parsed['user'] ?? '{}', true);
        return is_array($userData) ? $userData : null;
    }

    private function parseInitDataUnsafe(string $initData): ?array
    {
        $parsed = [];
        parse_str($initData, $parsed);
        $userData = json_decode($parsed['user'] ?? '{}', true);
        return is_array($userData) && !empty($userData['id']) ? $userData : null;
    }
}
