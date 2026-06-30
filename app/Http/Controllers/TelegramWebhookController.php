<?php

namespace App\Http\Controllers;

use App\Models\TelegramLink;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramService $telegram): void
    {
        $update = $request->all();
        $message = $update['message'] ?? [];

        if (!$message) {
            return;
        }

        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $from = $message['from'] ?? [];
        $telegramId = $from['id'] ?? null;
        $username = $from['username'] ?? null;

        if (!$telegramId) {
            return;
        }

        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $telegramId, $username, $text, $telegram);
        }
    }

    protected function handleStart(int $chatId, string $telegramId, ?string $username, string $text, TelegramService $telegram): void
    {
        $parts = explode(' ', $text, 2);
        $token = $parts[1] ?? null;

        if (!$token) {
            $telegram->sendMessage($chatId, "Welcome! Use the link code from your account settings to connect.");
            return;
        }

        $link = TelegramLink::where('link_token', $token)->first();

        if (!$link) {
            $telegram->sendMessage($chatId, "Invalid or expired link code. Please generate a new one from your account settings.");
            return;
        }

        $link->update([
            'telegram_id' => (string) $telegramId,
            'username' => $username,
            'chat_id' => $chatId,
            'link_token' => null,
            'notifications_enabled' => true,
        ]);

        $telegram->sendMessage(
            $chatId,
            "✅ <b>Successfully linked!</b>\n\n"
                . "Your account <b>{$link->user->name}</b> is now connected.\n"
                . "You will receive order updates here."
        );
    }
}
