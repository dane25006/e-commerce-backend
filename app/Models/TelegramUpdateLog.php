<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramUpdateLog extends Model
{
    protected $fillable = [
        'update_id',
        'chat_id',
        'type',
        'intent',
        'raw_payload',
        'is_processed',
        'job_uuid',
        'error_message',
        'processing_time_ms',
        'ip_address',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'is_processed' => 'boolean',
        'processing_time_ms' => 'integer',
    ];

    public function scopeProcessed($query)
    {
        return $query->where('is_processed', true);
    }

    public function scopeFailed($query)
    {
        return $query->whereNotNull('error_message');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChat($query, string $chatId)
    {
        return $query->where('chat_id', $chatId);
    }

    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
