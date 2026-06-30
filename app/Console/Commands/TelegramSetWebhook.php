<?php

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class TelegramSetWebhook extends Command
{
    protected $signature = 'telegram:webhook {action : set or info} {url? : Webhook URL (only for set)}';
    protected $description = 'Manage the Telegram bot webhook';

    public function handle(TelegramService $telegram): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'set' => $this->setWebhook($telegram),
            'info' => $this->webhookInfo($telegram),
            default => throw new \InvalidArgumentException("Action must be 'set' or 'info'"),
        };
    }

    protected function setWebhook(TelegramService $telegram): int
    {
        $url = $this->argument('url') ?? config('app.url') . '/api/telegram/webhook';

        $this->info("Setting webhook to: {$url}");

        $result = $telegram->setWebhook($url);

        if ($result && ($result['ok'] ?? false)) {
            $this->info('Webhook set successfully!');
            return self::SUCCESS;
        }

        $this->error('Failed to set webhook: ' . ($result['description'] ?? 'Unknown error'));
        return self::FAILURE;
    }

    protected function webhookInfo(TelegramService $telegram): int
    {
        $result = $telegram->getWebhookInfo();

        if (!$result || !($result['ok'] ?? false)) {
            $this->error('Failed to get webhook info');
            return self::FAILURE;
        }

        $info = $result['result'];

        $this->info('Webhook URL: ' . ($info['url'] ?? '(not set)'));
        $this->info('Has custom certificate: ' . ($info['has_custom_certificate'] ? 'Yes' : 'No'));
        $this->info('Pending updates: ' . ($info['pending_update_count'] ?? 0));
        $this->info('Last error: ' . ($info['last_error_message'] ?? '(none)'));
        $this->info('Max connections: ' . ($info['max_connections'] ?? 40));

        return self::SUCCESS;
    }
}
