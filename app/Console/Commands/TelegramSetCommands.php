<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBotService;
use Illuminate\Console\Command;

class TelegramSetCommands extends Command
{
    protected $signature = 'telegram:set-commands';
    protected $description = 'Set the bot command menu (shows when user types /)';

    public function handle(TelegramBotService $bot): int
    {
        $this->info('Setting bot command menu...');

        if ($bot->setMyCommands()) {
            $this->info('✅ Bot command menu updated!');
            $this->line('Users will see suggestions when typing "/" in Telegram.');
            $this->line('');
            $this->line('Commands set:');
            $this->line('  /start   — Home / Connect your account');
            $this->line('  /orders  — View your recent orders');
            $this->line('  /track   — Track your latest shipment');
            $this->line('  /cancel  — Cancel a pending order');
            $this->line('  /profile — View your account info');
            $this->line('  /shop    — Browse our products');
            $this->line('  /help    — See all available commands');
            $this->line('  /support — Contact customer service');
            return self::SUCCESS;
        }

        $this->error('❌ Failed to set commands. Check bot token.');
        return self::FAILURE;
    }
}
