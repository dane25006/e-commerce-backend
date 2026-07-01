<?php

namespace App\Services\Telegram;

use App\Models\TelegramAdminChat;
use App\Models\TelegramRateLimit;
use App\Models\TelegramUpdateLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TelegramSecurityService
{
    public function validateWebhookToken(Request $request): bool
    {
        $expected = config('telegram.webhook_secret');
        if (! $expected) {
            return true;
        }

        $queryToken = $request->query('token');
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($queryToken && hash_equals($expected, $queryToken)) {
            return true;
        }

        if ($headerToken && hash_equals($expected, $headerToken)) {
            return true;
        }

        Log::warning('Telegram: Invalid webhook secret token', [
            'ip' => $request->ip(),
        ]);

        return false;
    }

    public function isDuplicateUpdate(int $updateId): bool
    {
        $key = "tg_update_{$updateId}";

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, 3600);

        $exists = TelegramUpdateLog::where('update_id', $updateId)->exists();
        if ($exists) {
            return true;
        }

        return false;
    }

    public function isRateLimited(string $chatId): bool
    {
        $maxPerMinute = config('telegram.rate_limit_per_minute', 30);

        $count = TelegramRateLimit::countInWindow($chatId);

        if ($count >= $maxPerMinute) {
            Log::warning("Telegram: Rate limit exceeded for chat {$chatId}", [
                'count' => $count,
                'max'   => $maxPerMinute,
            ]);
            return true;
        }

        try {
            TelegramRateLimit::incrementCount($chatId);
        } catch (\Exception $e) {
            // Non-critical
        }

        return false;
    }

    public function isDuplicateCallback(string $data, string $chatId): bool
    {
        $key = 'tg_cb_' . md5($data . '_' . $chatId);

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, 5);
        return false;
    }

    public function authorizeAdmin(string $chatId): ?TelegramAdminChat
    {
        $admin = TelegramAdminChat::where('chat_id', $chatId)
            ->where('is_active', true)
            ->first();

        if (! $admin) {
            Log::warning("Telegram: Unauthorized admin action from chat {$chatId}");
        }

        return $admin;
    }

    public function authorizeAdminAction(string $chatId, string $action): bool
    {
        $admin = $this->authorizeAdmin($chatId);
        if (! $admin) {
            return false;
        }

        return $admin->can($action);
    }

    public function sanitizeCallbackData(string $data): string
    {
        return strip_tags(trim($data));
    }

    public function extractChatId(array $payload): ?string
    {
        if (isset($payload['message']['chat']['id'])) {
            return (string) $payload['message']['chat']['id'];
        }
        if (isset($payload['callback_query']['message']['chat']['id'])) {
            return (string) $payload['callback_query']['message']['chat']['id'];
        }
        return null;
    }

    public function detectUpdateType(array $payload): string
    {
        if (isset($payload['message'])) return 'message';
        if (isset($payload['callback_query'])) return 'callback_query';
        if (isset($payload['inline_query'])) return 'inline_query';
        if (isset($payload['chosen_inline_result'])) return 'chosen_inline_result';
        return 'unknown';
    }

    public function validatePayload(array $payload): ?string
    {
        if (empty($payload)) {
            return 'empty_payload';
        }

        if (! isset($payload['update_id'])) {
            return 'missing_update_id';
        }

        if (! isset($payload['message']) && ! isset($payload['callback_query'])
            && ! isset($payload['inline_query']) && ! isset($payload['my_chat_member'])) {
            return 'unsupported_type';
        }

        if (isset($payload['callback_query']['data'])) {
            $data = $payload['callback_query']['data'];
            if (! preg_match('/^[a-zA-Z0-9_-]+$/', $data) && ! str_contains($data, '_')) {
                return 'invalid_callback_format';
            }
        }

        return null;
    }
}
