<?php

namespace App\Services\Telegram;

use App\Models\TelegramFailedMessage;
use App\Models\TelegramMessageLog;
use App\Models\TelegramUpdateLog;
use Illuminate\Support\Facades\Log;

class TelegramLogService
{
    public function logIncomingUpdate(array $data): TelegramUpdateLog
    {
        if (! config('telegram.log_incoming')) {
            return new TelegramUpdateLog();
        }

        return TelegramUpdateLog::create([
            'update_id'   => $data['update_id'] ?? null,
            'chat_id'     => $data['chat_id'] ?? null,
            'type'        => $data['type'] ?? 'unknown',
            'intent'      => $data['intent'] ?? null,
            'raw_payload' => $data['raw_payload'] ?? null,
            'ip_address'  => $data['ip_address'] ?? null,
            'is_processed'=> false,
        ]);
    }

    public function markUpdateProcessed(int $logId, ?string $intent = null, ?int $timeMs = null): void
    {
        TelegramUpdateLog::where('id', $logId)->update(array_filter([
            'is_processed'      => true,
            'intent'            => $intent,
            'processing_time_ms'=> $timeMs,
        ]));
    }

    public function markUpdateFailed(int $logId, string $error): void
    {
        TelegramUpdateLog::where('id', $logId)->update([
            'error_message' => $error,
        ]);
    }

    public function logSentMessage(array $data): TelegramMessageLog
    {
        if (! config('telegram.log_outgoing')) {
            return new TelegramMessageLog();
        }

        return TelegramMessageLog::create([
            'chat_id'             => $data['chat_id'],
            'direction'           => $data['direction'] ?? 'outgoing',
            'type'                => $data['type'] ?? 'text',
            'text_preview'        => $data['text_preview'] ?? mb_substr($data['full_text'] ?? '', 0, 255),
            'full_text'           => $data['full_text'] ?? null,
            'buttons'             => $data['buttons'] ?? null,
            'telegram_message_id' => $data['telegram_message_id'] ?? null,
            'is_delivered'        => $data['is_delivered'] ?? true,
            'error_message'       => $data['error_message'] ?? null,
            'sent_at'             => $data['sent_at'] ?? now(),
        ]);
    }

    public function logFailure(array $data): TelegramFailedMessage
    {
        if (! config('telegram.log_failures')) {
            return new TelegramFailedMessage();
        }

        return TelegramFailedMessage::create([
            'chat_id'           => $data['chat_id'] ?? null,
            'job_class'         => $data['job_class'] ?? 'unknown',
            'payload'           => $data['payload'] ?? null,
            'exception_message' => $data['error'] ?? $data['exception_message'] ?? '',
            'retry_count'       => $data['attempt'] ?? 0,
            'max_retries'       => config('telegram.max_retries', 3),
            'last_attempt_at'   => now(),
        ]);
    }

    public function markUserInactive(string $chatId): void
    {
        \App\Models\TelegramLink::where('telegram_chat_id', $chatId)
            ->update(['notifications_enabled' => false]);
    }

    public function getRecentLogs(int $limit = 20): array
    {
        $updates = TelegramUpdateLog::latest()->take($limit)->get()->toArray();
        $messages = TelegramMessageLog::latest()->take($limit)->get()->toArray();

        $combined = array_merge($updates, $messages);
        usort($combined, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

        return array_slice($combined, 0, $limit);
    }

    public function getStats(): array
    {
        $last24h = now()->subHours(24);

        return [
            'updates_24h' => TelegramUpdateLog::where('created_at', '>=', $last24h)->count(),
            'failed_24h'  => TelegramUpdateLog::where('created_at', '>=', $last24h)
                ->whereNotNull('error_message')->count(),
            'messages_sent_24h' => TelegramMessageLog::where('created_at', '>=', $last24h)
                ->where('direction', 'outgoing')->count(),
            'pending_updates' => TelegramUpdateLog::where('is_processed', false)->count(),
            'unresolved_failed' => TelegramFailedMessage::where('is_resolved', false)->count(),
        ];
    }

    public function cleanupOldLogs(): int
    {
        $days = config('telegram.log_retention_days', 30);
        $cutoff = now()->subDays($days);

        $deleted = 0;
        $deleted += TelegramUpdateLog::where('created_at', '<', $cutoff)->delete();
        $deleted += TelegramMessageLog::where('created_at', '<', $cutoff)->delete();
        $deleted += TelegramFailedMessage::where('created_at', '<', $cutoff)
            ->where('is_resolved', true)->delete();

        Log::info("Telegram: Cleaned up {$deleted} old log entries");

        return $deleted;
    }
}
