<?php

namespace App\Jobs;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        protected string|int $chatId,
        protected string $text,
        protected ?array $buttons = null
    ) {}

    public function uniqueId(): string
    {
        return 'telegram_' . $this->chatId . '_' . md5($this->text);
    }

    public function handle(TelegramBotService $bot): void
    {
        $result = $bot->sendMessage($this->chatId, $this->text, $this->buttons);

        if ($result === null) {
            Log::warning('Telegram message send returned null', [
                'chat_id' => $this->chatId,
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Telegram message job failed permanently', [
            'chat_id' => $this->chatId,
            'error'   => $e->getMessage(),
        ]);
    }
}
