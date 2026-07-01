<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramFailedMessage extends Model
{
    protected $fillable = [
        'chat_id',
        'job_class',
        'payload',
        'exception_message',
        'exception_trace',
        'retry_count',
        'max_retries',
        'last_attempt_at',
        'is_resolved',
        'resolved_at',
    ];

    protected $casts = [
        'payload'        => 'array',
        'retry_count'    => 'integer',
        'max_retries'    => 'integer',
        'is_resolved'    => 'boolean',
        'last_attempt_at'=> 'datetime',
        'resolved_at'    => 'datetime',
    ];

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeByChat($query, string $chatId)
    {
        return $query->where('chat_id', $chatId);
    }

    public function retry(): bool
    {
        $jobClass = $this->job_class;
        $payload = $this->payload;

        if (! class_exists($jobClass)) {
            return false;
        }

        $job = new $jobClass(
            $payload['chat_id'] ?? $this->chat_id,
            $payload['text'] ?? '',
            $payload['buttons'] ?? null
        );

        dispatch($job);

        $this->update([
            'retry_count' => $this->retry_count + 1,
            'is_resolved' => true,
            'resolved_at' => now(),
        ]);

        return true;
    }
}
