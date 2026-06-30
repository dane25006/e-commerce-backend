<?php

namespace App\Services;

use App\Models\Order;
use App\Models\TelegramLink;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected Client $http;
    protected string $token;
    protected string $apiBase;

    public function __construct()
    {
        $this->token = config('services.telegram.bot_token');
        $this->apiBase = "https://api.telegram.org/bot{$this->token}/";
        $this->http = new Client(['base_uri' => $this->apiBase]);
    }

    public function sendMessage(int|string|null $chatId, string $text, array $extra = []): ?array
    {
        if ($chatId === null) {
            return null;
        }

        try {
            $response = $this->http->post('sendMessage', [
                'json' => array_merge([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ], $extra),
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (TransferException $e) {
            Log::error('Telegram sendMessage failed: ' . $e->getMessage());
            return null;
        }
    }

    public function setWebhook(string $url): ?array
    {
        try {
            $response = $this->http->post('setWebhook', [
                'json' => ['url' => $url],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (TransferException $e) {
            Log::error('Telegram setWebhook failed: ' . $e->getMessage());
            return null;
        }
    }

    public function getWebhookInfo(): ?array
    {
        try {
            $response = $this->http->post('getWebhookInfo');

            return json_decode($response->getBody()->getContents(), true);
        } catch (TransferException $e) {
            Log::error('Telegram getWebhookInfo failed: ' . $e->getMessage());
            return null;
        }
    }

    public function sendOrderNotification(Order $order, ?string $statusLabel = null): void
    {
        $link = TelegramLink::where('user_id', $order->user_id)
            ->where('notifications_enabled', true)
            ->whereNotNull('chat_id')
            ->first();

        if (!$link) {
            return;
        }

        $items = $order->items()->with('product')->get();
        $itemsList = $items->map(fn($item) =>
            "• {$item->product?->name} x{$item->quantity} — \${$item->price}"
        )->implode("\n");

        $status = $statusLabel ?? $order->status;

        $message = "<b>🛒 Order #{$order->id}</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Status: <b>{$status}</b>\n"
            . "Total: <b>\${$order->total}</b>\n\n"
            . "<b>Items:</b>\n{$itemsList}\n\n"
            . "📅 " . $order->created_at->format('M d, Y H:i');

        $this->sendMessage($link->chat_id, $message);
    }

    public function sendTestMessage(int|string $chatId): ?array
    {
        return $this->sendMessage($chatId, 'Test notification — your Telegram is linked successfully!');
    }
}
