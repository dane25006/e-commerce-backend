<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $telegram_id
 * @property string|null $username
 * @property int|null $chat_id
 * @property string|null $link_token
 * @property bool $notifications_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 */
class TelegramLink extends Model
{
    protected $fillable = [
        'user_id',
        'telegram_id',
        'username',
        'chat_id',
        'link_token',
        'notifications_enabled',
    ];

    protected $casts = [
        'notifications_enabled' => 'boolean',
        'chat_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
