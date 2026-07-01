<?php

namespace App\Console\Commands;

use App\Models\TelegramAdminChat;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class TelegramAdminCommand extends Command
{
    protected $signature = 'telegram:admin {chatId? : Chat ID to register}';
    protected $description = 'Register or find admin chat IDs for Telegram notifications';

    public function handle(): int
    {
        $chatId = $this->argument('chatId');

        if ($chatId) {
            return $this->registerAdmin($chatId);
        }

        // Try fetching recent updates
        $token = config('telegram.bot_token') ?: config('services.telegram.bot_token');
        if (! $token) {
            $this->error('TELEGRAM_BOT_TOKEN is not set in .env');
            return self::FAILURE;
        }

        $client = new Client(['base_uri' => "https://api.telegram.org/bot{$token}/"]);

        try {
            $response = $client->post('getUpdates', ['json' => ['limit' => 20]]);
            $result = json_decode($response->getBody(), true);
            $updates = $result['result'] ?? [];

            if (empty($updates)) {
                $this->error('No recent messages found.');
                $this->line('1. Message @' . config('telegram.bot_username') . ' on Telegram');
                $this->line('2. Run: php artisan telegram:admin');
                $this->newLine();
                $this->line('Or get your ID from @userinfobot and set it directly:');
                $this->line('  php artisan telegram:admin 123456789');
                return self::FAILURE;
            }

            $chats = [];
            foreach ($updates as $update) {
                $msg = $update['message'] ?? [];
                $chat = $msg['chat'] ?? [];
                $id = $chat['id'] ?? null;
                if ($id && ! isset($chats[$id])) {
                    $chats[$id] = [
                        'id' => $id,
                        'name' => $chat['first_name'] ?? 'unknown',
                        'username' => $chat['username'] ?? null,
                        'type' => $chat['type'] ?? 'private',
                    ];
                }
            }

            if (count($chats) === 1) {
                $c = reset($chats);
                $this->line("Found: {$c['name']} (@{$c['username']}) — ID: {$c['id']}");
                if ($this->confirm('Register this as admin?', true)) {
                    return $this->registerAdmin($c['id']);
                }
                return self::FAILURE;
            }

            $this->info('Multiple chats found. Choose one:');
            $choices = [];
            foreach ($chats as $c) {
                $choices[] = "{$c['name']} (@{$c['username']}) — {$c['id']}";
            }
            $selected = $this->choice('Which chat should be admin?', $choices);
            preg_match('/— (\d+)$/', $selected, $m);
            $selectedId = $m[1] ?? null;

            if ($selectedId && $this->confirm('Register this as admin?', true)) {
                return $this->registerAdmin($selectedId);
            }

        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");
            $this->line('Set directly: php artisan telegram:admin 123456789');
            return self::FAILURE;
        }

        return self::FAILURE;
    }

    protected function registerAdmin(string $chatId): int
    {
        TelegramAdminChat::updateOrCreate(
            ['chat_id' => $chatId],
            [
                'is_active' => true,
                'role' => 'super_admin',
                'name' => $this->ask('Admin name (optional)', 'Admin'),
            ]
        );

        // Also update .env for backward compatibility
        $this->updateEnv($chatId);

        $this->info("✅ Admin registered: {$chatId}");
        $this->warn('Run php artisan config:clear to apply .env changes.');
        $this->line('Then run: php artisan telegram:poll and php artisan queue:work');

        return self::SUCCESS;
    }

    protected function updateEnv(string $chatId): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) return;

        $content = file_get_contents($envPath);

        if (preg_match('/TELEGRAM_ADMIN_CHAT_ID=.*/', $content)) {
            $content = preg_replace('/TELEGRAM_ADMIN_CHAT_ID=.*/', "TELEGRAM_ADMIN_CHAT_ID={$chatId}", $content);
        } else {
            $content .= "\nTELEGRAM_ADMIN_CHAT_ID={$chatId}\n";
        }

        file_put_contents($envPath, $content);
    }
}
