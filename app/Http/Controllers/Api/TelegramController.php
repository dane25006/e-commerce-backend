<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\TelegramLink;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramWebhookService $webhook
    ) {}

    public function webhook(Request $request): void
    {
        try {
            $this->webhook->handle($request->all());
        } catch (\Exception $e) {
            Log::error('Telegram webhook handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $link = TelegramLink::where('user_id', $user->id)->first();

        return response()->json([
            'linked'                => $link ? $link->isVerified() : false,
            'telegram_username'     => $link?->telegram_username,
            'telegram_chat_id'      => $link?->telegram_chat_id,
            'notifications_enabled' => $link?->notifications_enabled ?? false,
        ]);
    }

    public function generateCode(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $code = TelegramLink::generateVerificationCode();

        TelegramLink::updateOrCreate(
            ['user_id' => $user->id],
            [
                'verification_code'   => $code,
                'notifications_enabled' => true,
            ]
        );

        return response()->json([
            'verification_code' => $code,
            'bot_username'      => config('services.telegram.bot_username'),
            'deep_link'         => 'https://t.me/' . config('services.telegram.bot_username') . '?start=' . $code,
        ]);
    }

    public function toggleNotifications(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $link = TelegramLink::where('user_id', $user->id)->firstOrFail();
        $link->update([
            'notifications_enabled' => $request->boolean('enabled', true),
        ]);

        return response()->json([
            'notifications_enabled' => $link->notifications_enabled,
        ]);
    }

    public function unlink(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        TelegramLink::where('user_id', $user->id)->delete();

        return $this->success(null, 'Telegram disconnected successfully.');
    }

    public function sendTest(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $link = TelegramLink::where('user_id', $user->id)->first();

        if (! $link || ! $link->isVerified()) {
            return $this->notFound('No Telegram link found. Please connect your account first.');
        }

        $result = $this->bot->sendMessage(
            $link->telegram_chat_id,
            "✅ <b>Test Notification</b>\n\nYour Telegram is connected correctly!\nYou will receive order updates here."
        );

        if ($result) {
            return $this->success(null, 'Test message sent!');
        }

        return $this->error('Failed to send test message. Please check your connection.', 500);
    }
}
