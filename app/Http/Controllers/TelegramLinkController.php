<?php

namespace App\Http\Controllers;

use App\Models\TelegramLink;
use App\Services\TelegramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TelegramLinkController extends Controller
{
    public function generateLink(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = Str::random(32);

        TelegramLink::updateOrCreate(
            ['user_id' => $user->id],
            ['link_token' => $token]
        );

        return response()->json([
            'link_code' => $token,
            'bot_username' => config('services.telegram.bot_username'),
            'deep_link' => "https://t.me/" . config('services.telegram.bot_username') . "?start={$token}",
        ]);
    }

    public function status(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $link = TelegramLink::where('user_id', $user->id)->first();

        return response()->json([
            'linked' => (bool) $link,
            'telegram_username' => $link?->username,
            'notifications_enabled' => $link?->notifications_enabled ?? false,
        ]);
    }

    public function toggleNotifications(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $link = TelegramLink::where('user_id', $user->id)->firstOrFail();
        $link->update([
            'notifications_enabled' => $request->boolean('enabled', true),
        ]);

        return response()->json(['notifications_enabled' => $link->notifications_enabled]);
    }

    public function generate(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $token = Str::random(32);

        TelegramLink::updateOrCreate(
            ['user_id' => $user->id],
            ['link_token' => $token]
        );

        return response()->json([
            'deep_link' => 'https://t.me/' . config('services.telegram.bot_username') . '?start=' . $token,
        ]);
    }

    public function destroy(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        TelegramLink::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Telegram disconnected']);
    }

    public function unlink(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        TelegramLink::where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Telegram unlinked successfully']);
    }

    public function sendTest(TelegramService $telegram): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $link = TelegramLink::where('user_id', $user->id)->first();

        if (!$link) {
            return response()->json(['message' => 'No Telegram link found'], 404);
        }

        $result = $telegram->sendTestMessage($link->chat_id);

        if ($result) {
            return response()->json(['message' => 'Test message sent']);
        }

        return response()->json(['message' => 'Failed to send test message'], 500);
    }
}
