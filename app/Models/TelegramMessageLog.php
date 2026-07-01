<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramMessageLog extends Model
{
    protected $fillable = [
        'chat_id',
        'direction',
        'type',
        'text_preview',
        'full_text',
        'buttons',
        'telegram_message_id',
        'is_delivered',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'buttons' => 'array',
        'is_delivered' => 'boolean',
    ];

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeFailed($query)
    {
        return $query->where('is_delivered', false)->whereNotNull('error_message');
    }
}
