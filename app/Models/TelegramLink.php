<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TelegramLink extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'telegram_username',
        'verification_code',
        'verified_at',
        'notifications_enabled',
    ];

    protected $casts = [
        'verified_at'            => 'datetime',
        'notifications_enabled'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null && $this->telegram_chat_id !== null;
    }

    public static function generateVerificationCode(): string
    {
        return 'TG-' . strtoupper(Str::random(6));
    }
}
