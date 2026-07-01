<?php

namespace App\Services\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected ?Client $http = null;
    protected int $backoff = 100000; // 100ms in microseconds

    public function __construct()
    {
        $token = config('telegram.bot_token') ?: config('services.telegram.bot_token');
        if ($token) {
            $this->http = new Client([
                'base_uri' => "https://api.telegram.org/bot{$token}/",
                'timeout'  => 10,
                'connect_timeout' => 5,
                'http_errors' => true,
            ]);
        }
    }

    protected function client(): Client
    {
        if (! $this->http) {
            throw new \RuntimeException('Telegram bot token is not configured. Set TELEGRAM_BOT_TOKEN in .env');
        }
        return $this->http;
    }

    // ── SEND MESSAGE ─────────────────────────────────────────

    public function sendMessage(string|int $chatId, string $text, ?array $buttons = null): ?array
    {
        if (empty($chatId) || empty($text)) {
            Log::warning('TelegramBot: sendMessage skipped — empty chat_id or text');
            return null;
        }

        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];

        if ($buttons) {
            $payload['reply_markup'] = json_encode([
                'inline_keyboard' => $buttons,
            ]);
        }

        try {
            $this->enforceRateLimit();
            $response = $this->client()->post('sendMessage', ['json' => $payload]);
            $body = json_decode($response->getBody()->getContents(), true);

            if (! ($body['ok'] ?? false)) {
                throw new \RuntimeException($body['description'] ?? 'Unknown API error');
            }

            return $body;

        } catch (ClientException $e) {
            return $this->handleClientException($e, $chatId, $text);

        } catch (ConnectException $e) {
            Log::error("TelegramBot: Connection failed for chat {$chatId}: {$e->getMessage()}");
            throw $e;

        } catch (\Exception $e) {
            Log::error("TelegramBot: sendMessage error for chat {$chatId}: {$e->getMessage()}");
            throw $e;
        }
    }

    // ── OTHER API METHODS ─────────────────────────────────────

    public function sendChatAction(string|int $chatId, string $action = 'typing'): void
    {
        try {
            $this->client()->post('sendChatAction', [
                'json' => ['chat_id' => $chatId, 'action' => $action],
            ]);
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): void
    {
        try {
            $this->client()->post('answerCallbackQuery', [
                'json' => [
                    'callback_query_id' => $callbackQueryId,
                    'text'              => $text,
                    'show_alert'        => $showAlert,
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('TelegramBot: answerCallbackQuery failed', ['error' => $e->getMessage()]);
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = $this->client()->post('setWebhook', [
                'json' => [
                    'url' => $url,
                    'max_connections' => 40,
                    'allowed_updates' => ['message', 'callback_query'],
                ],
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('TelegramBot: setWebhook failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->client()->post('getWebhookInfo');
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('TelegramBot: getWebhookInfo failed: ' . $e->getMessage());
            return null;
        }
    }

    public function sendPhoto(string|int $chatId, string $photoPath, string $caption = ''): ?array
    {
        try {
            $response = $this->client()->post('sendPhoto', [
                'multipart' => [
                    ['name' => 'chat_id', 'contents' => $chatId],
                    ['name' => 'photo', 'contents' => fopen($photoPath, 'r')],
                    ['name' => 'caption', 'contents' => $caption],
                    ['name' => 'parse_mode', 'contents' => 'HTML'],
                ],
            ]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('TelegramBot: sendPhoto failed', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
            return null;
        }
    }

    public function editMessageText(string|int $chatId, int $messageId, string $text, ?array $buttons = null): ?array
    {
        $payload = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ];
        if ($buttons) {
            $payload['reply_markup'] = json_encode(['inline_keyboard' => $buttons]);
        }
        try {
            $response = $this->client()->post('editMessageText', ['json' => $payload]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::warning('TelegramBot: editMessageText failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function setMyCommands(): bool
    {
        $commands = [
            ['command' => 'start',     'description' => '🏠 Start — Connect your account or main menu'],
            ['command' => 'menu',      'description' => '📋 Menu — Show main menu'],
            ['command' => 'chat',      'description' => '🧠 AI Chat — Ask me anything'],
            ['command' => 'image',     'description' => '🖼 Image — Generate AI images'],
            ['command' => 'products',  'description' => '🛍 Products — Browse categories'],
            ['command' => 'cart',      'description' => '🛒 Cart — View your shopping cart'],
            ['command' => 'orders',    'description' => '📦 Orders — View your orders'],
            ['command' => 'track',     'description' => '🚚 Track — Track shipment'],
            ['command' => 'cancel',    'description' => '❌ Cancel — Cancel pending order'],
            ['command' => 'profile',   'description' => '👤 Profile — Your account info'],
            ['command' => 'help',      'description' => '❓ Help — Available commands'],
        ];

        try {
            $response = $this->client()->post('setMyCommands', [
                'json' => ['commands' => $commands],
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('TelegramBot: setMyCommands failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteWebhook(): bool
    {
        try {
            $response = $this->client()->post('deleteWebhook');
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['ok'] ?? false;
        } catch (\Exception $e) {
            Log::error('TelegramBot: deleteWebhook failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getMe(): ?array
    {
        try {
            $response = $this->client()->post('getMe');
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            Log::error('TelegramBot: getMe failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── ERROR HANDLING ────────────────────────────────────────

    protected function handleClientException(ClientException $e, int|string $chatId, string $text): ?array
    {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;
        $body = $response ? json_decode($response->getBody()->getContents(), true) : [];
        $errorCode = $body['error_code'] ?? 0;
        $description = $body['description'] ?? 'Unknown error';

        Log::warning("Telegram API error [{$statusCode}]: {$description}", [
            'chat_id' => $chatId,
            'code'    => $errorCode,
        ]);

        return match ($statusCode) {
            400 => $this->handle400($chatId, $description, $text),
            403 => $this->handle403($chatId, $description),
            404 => $this->handle404($chatId, $description),
            429 => $this->handle429($chatId, $body),
            default => throw $e,
        };
    }

    protected function handle400(int|string $chatId, string $description, string $text): ?array
    {
        if (str_contains($description, 'chat not found')) {
            Log::warning("Telegram: Chat {$chatId} not found. User may have blocked bot.");
            app(\App\Services\Telegram\TelegramLogService::class)->markUserInactive($chatId);
            return null;
        }

        if (str_contains($description, 'BUTTON_DATA_INVALID')) {
            Log::error("Telegram: Invalid button data for chat {$chatId}. Retrying without buttons.");
            return $this->sendMessage($chatId, $text, null);
        }

        if (str_contains($description, 'message is too long')) {
            $truncated = mb_substr($text, 0, 4000) . "\n\n…(truncated)";
            return $this->sendMessage($chatId, $truncated, null);
        }

        if (str_contains($description, 'Can\'t parse entities')) {
            $cleanText = strip_tags($text);
            return $this->sendMessage($chatId, $cleanText, null);
        }

        throw new \RuntimeException("Telegram 400: {$description}");
    }

    protected function handle403(int|string $chatId, string $description): ?array
    {
        if (str_contains($description, 'bot was blocked')) {
            Log::info("Telegram: Bot blocked by user {$chatId}");
            app(\App\Services\Telegram\TelegramLogService::class)->markUserInactive($chatId);
            return null;
        }

        if (str_contains($description, 'bot can\'t initiate conversation')) {
            Log::info("Telegram: User {$chatId} hasn't started the bot");
            return null;
        }

        if (str_contains($description, 'not enough rights')) {
            Log::warning("Telegram: Bot lacks rights in group {$chatId}");
            return null;
        }

        throw new \RuntimeException("Telegram 403: {$description}");
    }

    protected function handle404(int|string $chatId, string $description): ?array
    {
        Log::warning("Telegram: Resource not found for chat {$chatId}: {$description}");
        return null;
    }

    protected function handle429(int|string $chatId, array $body): ?array
    {
        $retryAfter = $body['parameters']['retry_after'] ?? 5;
        Log::warning("Telegram: Rate limited for chat {$chatId}. Retry after {$retryAfter}s");
        throw new \RuntimeException("Rate limited. Retry after {$retryAfter}s", 429);
    }

    protected function enforceRateLimit(): void
    {
        $key = 'tg_global_rate';
        $count = (int) cache()->get($key, 0);

        if ($count >= 30) {
            $this->backoff = min($this->backoff * 2, 1000000);
            usleep($this->backoff);
        } else {
            $this->backoff = 100000;
        }

        cache()->put($key, $count + 1, 2);
    }
}
