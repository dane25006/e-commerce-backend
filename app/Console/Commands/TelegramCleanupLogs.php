<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramLogService;
use Illuminate\Console\Command;

class TelegramCleanupLogs extends Command
{
    protected $signature = 'telegram:cleanup-logs';
    protected $description = 'Delete old Telegram log entries';

    public function handle(TelegramLogService $logger): int
    {
        $deleted = $logger->cleanupOldLogs();
        $this->info("✅ Deleted {$deleted} old log entries.");
        return self::SUCCESS;
    }
}
