<?php

namespace App\Jobs\Telegram;

use App\Models\TelegramFailedMessage;
use App\Models\TelegramMessageLog;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramLogService;
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
    ) {
        $this->onQueue('telegram');
    }

    public function uniqueId(): string
    {
        return 'tg_send_' . $this->chatId . '_' . md5($this->text);
    }

    public function handle(TelegramBotService $bot, TelegramLogService $logger): void
    {
        // ── Validate ──────────────────────────────────────────
        if (empty($this->chatId)) {
            Log::warning('Telegram: Attempted to send to empty chat_id');
            $this->delete();
            return;
        }

        if (mb_strlen($this->text) > 4096) {
            Log::warning('Telegram: Message too long (' . mb_strlen($this->text) . ' chars), truncating');
            $this->text = mb_substr($this->text, 0, 4000) . "\n\n…(truncated)";
        }

        // ── Send ──────────────────────────────────────────────
        try {
            $result = $bot->sendMessage($this->chatId, $this->text, $this->buttons);

            if ($result === null) {
                throw new \RuntimeException('Telegram API returned null (likely blocked/unauthorized)');
            }

            $logger->logSentMessage([
                'chat_id'             => $this->chatId,
                'direction'           => 'outgoing',
                'type'                => 'text',
                'text_preview'        => mb_substr($this->text, 0, 255),
                'full_text'           => $this->text,
                'buttons'             => $this->buttons,
                'telegram_message_id' => $result['result']['message_id'] ?? null,
                'is_delivered'        => true,
                'sent_at'             => now(),
            ]);

        } catch (\Exception $e) {
            $this->recordFailure($e, $logger);
            throw $e;
        }
    }

    protected function recordFailure(\Exception $e, TelegramLogService $logger): void
    {
        $attempt = $this->attempts();

        // Log to failed_messages table
        try {
            TelegramFailedMessage::create([
                'chat_id'           => $this->chatId,
                'job_class'         => static::class,
                'payload'           => [
                    'chat_id'  => $this->chatId,
                    'text'     => $this->text,
                    'buttons'  => $this->buttons,
                    'attempt'  => $attempt,
                ],
                'exception_message' => $e->getMessage(),
                'retry_count'       => $attempt,
                'max_retries'       => $this->tries,
                'last_attempt_at'   => now(),
            ]);
        } catch (\Exception $logError) {
            // Non-critical
        }

        $logger->logFailure([
            'chat_id'  => $this->chatId,
            'error'    => $e->getMessage(),
            'attempt'  => $attempt,
            'job_class'=> static::class,
        ]);

        // Send fallback on last attempt
        if ($attempt >= $this->tries) {
            try {
                $bot = app(TelegramBotService::class);
                $bot->sendMessage(
                    $this->chatId,
                    "⚠️ Sorry, something went wrong. Please try again later."
                );
            } catch (\Exception $fallbackError) {
                // Silently fail
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Telegram: Message job permanently failed', [
            'chat_id' => $this->chatId,
            'error'   => $e->getMessage(),
        ]);
    }
}
