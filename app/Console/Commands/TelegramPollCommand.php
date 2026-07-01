<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramWebhookService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class TelegramPollCommand extends Command
{
    protected $signature = 'telegram:poll';
    protected $description = 'Poll Telegram for updates (use instead of webhook in dev)';

    public function handle(TelegramWebhookService $webhook): int
    {
        $token = config('services.telegram.bot_token');
        $client = new Client(['base_uri' => "https://api.telegram.org/bot{$token}/"]);
        $offset = 0;

        $this->info('Polling for Telegram updates... (Ctrl+C to stop)');

        while (true) {
            try {
                $response = $client->post('getUpdates', [
                    'json' => [
                        'offset'  => $offset,
                        'timeout' => 30,
                    ],
                ]);

                $result = json_decode($response->getBody(), true);
                $updates = $result['result'] ?? [];

                foreach ($updates as $update) {
                    $webhook->handle($update);
                    $offset = $update['update_id'] + 1;
                }
            } catch (\Exception $e) {
                $this->error('Poll error: ' . $e->getMessage());
                sleep(5);
            }
        }
    }
}
