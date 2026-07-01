<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramCheckHealth extends Command
{
    protected $signature = 'telegram:check-health';
    protected $description = 'Check Telegram bot health and webhook status';

    public function handle(TelegramBotService $bot): int
    {
        $token = config('telegram.bot_token') ?: config('services.telegram.bot_token');

        if (! $token) {
            $this->error('❌ BOT TOKEN MISSING');
            Log::critical('Telegram health: Bot token missing');
            return self::FAILURE;
        }

        // Check getMe
        $me = $bot->getMe();
        if (! $me || ! ($me['ok'] ?? false)) {
            $this->error('❌ Cannot reach Telegram API');
            Log::critical('Telegram health: Cannot reach API');
            return self::FAILURE;
        }

        $botName = $me['result']['username'] ?? 'unknown';
        $this->line("✅ Bot @{$botName} is reachable");

        // Check webhook
        $info = $bot->getWebhookInfo();
        if (! $info) {
            $this->warn('⚠️ Could not get webhook info');
        } else {
            $url = $info['result']['url'] ?? '';
            $pending = $info['result']['pending_update_count'] ?? 0;
            $lastError = $info['result']['last_error_message'] ?? null;
            $expected = config('telegram.webhook_url');

            if ($url !== $expected) {
                $this->warn("⚠️ Webhook URL mismatch");
                $this->line("   Expected: {$expected}");
                $this->line("   Current:  {$url}");

                if ($this->confirm('Fix webhook URL?')) {
                    if ($bot->setWebhook($expected)) {
                        $this->info('✅ Webhook updated');
                    } else {
                        $this->error('❌ Failed to set webhook');
                    }
                }
            } else {
                $this->line("✅ Webhook: {$url}");
            }

            if ($pending > 0) {
                $this->warn("⚠️ {$pending} pending updates — queue may be backed up");
            }

            if ($lastError) {
                $this->warn("⚠️ Last webhook error: {$lastError}");
            }
        }

        $this->line('✅ Health check completed');
        return self::SUCCESS;
    }
}
