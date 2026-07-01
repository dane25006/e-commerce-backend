<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

class TelegramBotCommand extends Command
{
    protected $signature = 'telegram:bot {action : webhook-set|webhook-info} {url? : Webhook URL}';
    protected $description = 'Manage the Telegram bot webhook';

    public function handle(TelegramBotService $bot): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'webhook-set'  => $this->setWebhook($bot),
            'webhook-info' => $this->webhookInfo($bot),
            default        => throw new \InvalidArgumentException("Action must be 'webhook-set' or 'webhook-info'"),
        };
    }

    protected function setWebhook(TelegramBotService $bot): int
    {
        $url = $this->argument('url') ?? config('app.url') . '/api/telegram/webhook';

        $this->info("Setting webhook to: {$url}");

        if ($bot->setWebhook($url)) {
            $this->info('✅ Webhook set successfully!');
            return self::SUCCESS;
        }

        $this->error('❌ Failed to set webhook.');
        return self::FAILURE;
    }

    protected function webhookInfo(TelegramBotService $bot): int
    {
        $result = $bot->getWebhookInfo();

        if (! $result || ! ($result['ok'] ?? false)) {
            $this->error('Failed to get webhook info');
            return self::FAILURE;
        }

        $info = $result['result'];
        $this->line('Webhook URL: ' . ($info['url'] ?: '(not set)'));
        $this->line('Pending updates: ' . ($info['pending_update_count'] ?? 0));
        $this->line('Last error: ' . ($info['last_error_message'] ?? '(none)'));

        return self::SUCCESS;
    }
}
