<?php

namespace App\Http\Controllers;

use App\Jobs\Telegram\ProcessTelegramUpdateJob;
use App\Services\Telegram\TelegramLogService;
use App\Services\Telegram\TelegramSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramWebhookController extends Controller
{
    public function __construct(
        protected TelegramSecurityService $security,
        protected TelegramLogService $logger
    ) {}

    public function handle(Request $request): \Illuminate\Http\Response
    {
        $startTime = microtime(true);
        $payload = $request->all();
        $ip = $request->ip();

        // ── 1. Validate payload ──────────────────────────────
        $validationError = $this->security->validatePayload($payload);
        if ($validationError) {
            Log::info("Telegram: Ignored update ({$validationError})");
            return response('OK', 200);
        }

        $updateId = $payload['update_id'];
        $chatId = $this->security->extractChatId($payload);
        $type = $this->security->detectUpdateType($payload);

        // ── 2. Deduplicate ───────────────────────────────────
        if ($this->security->isDuplicateUpdate($updateId)) {
            Log::debug("Telegram: Duplicate update {$updateId} ignored");
            return response('OK', 200);
        }

        // ── 3. Rate limit ────────────────────────────────────
        if ($chatId && $this->security->isRateLimited($chatId)) {
            Log::warning("Telegram: Rate limited chat {$chatId}");
            return response('OK', 200);
        }

        // ── 4. Log update ────────────────────────────────────
        $log = $this->logger->logIncomingUpdate([
            'update_id'  => $updateId,
            'chat_id'    => $chatId,
            'type'       => $type,
            'intent'     => null,
            'raw_payload'=> $payload,
            'ip_address' => $ip,
        ]);

        // ── 5. Queue processing ──────────────────────────────
        $jobUuid = (string) Str::uuid();
        $log->update(['job_uuid' => $jobUuid]);

        ProcessTelegramUpdateJob::dispatch($payload, $log->id)
            ->onQueue('telegram');

        // ── 6. Update processing time ────────────────────────
        $elapsed = (int) ((microtime(true) - $startTime) * 1000);
        $log->update(['processing_time_ms' => $elapsed]);

        // ── 7. Always 200 ────────────────────────────────────
        return response('OK', 200);
    }
}
