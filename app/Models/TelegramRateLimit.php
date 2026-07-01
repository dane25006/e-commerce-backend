<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TelegramRateLimit extends Model
{
    protected $fillable = [
        'chat_id',
        'endpoint',
        'request_count',
        'window_start',
    ];

    protected $casts = [
        'request_count' => 'integer',
        'window_start'  => 'datetime',
    ];

    public static function incrementCount(string $chatId, string $endpoint = 'webhook'): void
    {
        $windowStart = now()->startOfMinute();

        DB::statement("
            INSERT INTO telegram_rate_limits (chat_id, endpoint, window_start, request_count, created_at, updated_at)
            VALUES (?, ?, ?, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE request_count = request_count + 1, updated_at = NOW()
        ", [$chatId, $endpoint, $windowStart]);
    }

    public static function countInWindow(string $chatId, string $endpoint = 'webhook'): int
    {
        $windowStart = now()->subMinute();

        return (int) static::where('chat_id', $chatId)
            ->where('endpoint', $endpoint)
            ->where('window_start', '>=', $windowStart)
            ->sum('request_count');
    }
}
