<?php

namespace App\Jobs\Telegram;

use App\Models\TelegramUpdateLog;
use App\Services\Telegram\TelegramLogService;
use App\Services\Telegram\TelegramWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTelegramUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        protected array $payload,
        protected int $logId
    ) {
        $this->onQueue('telegram');
    }

    public function handle(TelegramWebhookService $webhook, TelegramLogService $logger): void
    {
        $startTime = microtime(true);

        try {
            $intent = $webhook->handle($this->payload);

            $elapsed = (int) ((microtime(true) - $startTime) * 1000);
            $logger->markUpdateProcessed($this->logId, $intent, $elapsed);

        } catch (\Exception $e) {
            $elapsed = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('Telegram: Update processing failed', [
                'log_id'  => $this->logId,
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            $logger->markUpdateFailed($this->logId, $e->getMessage());

            TelegramUpdateLog::where('id', $this->logId)->update([
                'processing_time_ms' => $elapsed,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('Telegram: Update job failed permanently', [
            'log_id' => $this->logId,
            'error'  => $e->getMessage(),
        ]);
    }
}
