<?php

namespace App\Services\Telegram;

use App\Models\ChatLog;

class ChatLogService
{
    public function log(int $userId, string $type, string $direction, string $message, ?string $response = null, ?string $intent = null, ?array $metadata = null, ?int $tokens = null): ChatLog
    {
        return ChatLog::create([
            'user_id'     => $userId,
            'chat_id'     => (string) $userId,
            'type'        => $type,
            'direction'   => $direction,
            'message'     => $message,
            'response'    => $response,
            'intent'      => $intent,
            'metadata'    => $metadata,
            'tokens_used' => $tokens,
        ]);
    }

    public function getHistory(int $userId, int $limit = 10): array
    {
        return ChatLog::where('user_id', $userId)
            ->whereIn('type', ['ai_chat', 'text'])
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values()
            ->toArray();
    }

    public function getStats(): array
    {
        $last24h = now()->subHours(24);

        return [
            'total_chats'   => ChatLog::count(),
            'chats_24h'     => ChatLog::where('created_at', '>=', $last24h)->count(),
            'ai_chats_24h'  => ChatLog::where('type', 'ai_chat')->where('created_at', '>=', $last24h)->count(),
            'images_24h'    => ChatLog::where('type', 'image_gen')->where('created_at', '>=', $last24h)->count(),
            'total_tokens'  => ChatLog::sum('tokens_used') ?? 0,
        ];
    }
}
