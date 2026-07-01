<?php

namespace App\Services\Telegram;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected ?Client $http = null;

    public function __construct()
    {
        $token = config('services.telegram.bot_token');
        if ($token) {
            $this->http = new Client([
                'base_uri' => "https://api.telegram.org/bot{$token}/",
                'timeout'  => 10,
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

    public function sendMessage(string|int $chatId, string $text, ?array $buttons = null): ?array
    {
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
            $response = $this->client()->post('sendMessage', ['json' => $payload]);
            return json_decode($response->getBody()->getContents(), true);
        } catch (TransferException $e) {
            Log::error('TelegramBot: sendMessage failed', [
                'chat_id' => $chatId,
                'error'   => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function sendChatAction(string|int $chatId, string $action = 'typing'): void
    {
        try {
            $this->client()->post('sendChatAction', [
                'json' => [
                    'chat_id' => $chatId,
                    'action'  => $action,
                ],
            ]);
        } catch (TransferException $e) {
            // Non-critical, silently ignore
        }
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = ''): void
    {
        try {
            $this->client()->post('answerCallbackQuery', [
                'json' => [
                    'callback_query_id' => $callbackQueryId,
                    'text'              => $text,
                    'show_alert'        => false,
                ],
            ]);
        } catch (TransferException $e) {
            Log::warning('TelegramBot: answerCallbackQuery failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setWebhook(string $url): bool
    {
        try {
            $response = $this->client()->post('setWebhook', [
                'json' => ['url' => $url],
            ]);
            $result = json_decode($response->getBody()->getContents(), true);
            return $result['ok'] ?? false;
        } catch (TransferException $e) {
            Log::error('TelegramBot: setWebhook failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->client()->post('getWebhookInfo');
            return json_decode($response->getBody()->getContents(), true);
        } catch (TransferException $e) {
            Log::error('TelegramBot: getWebhookInfo failed: ' . $e->getMessage());
            return null;
        }
    }
}
