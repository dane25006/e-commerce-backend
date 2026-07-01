<?php

namespace App\Console\Commands;

use App\Models\TelegramFailedMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TelegramRetryFailed extends Command
{
    protected $signature = 'telegram:retry-failed {--all : Retry all including resolved}';
    protected $description = 'Retry failed Telegram messages';

    public function handle(): int
    {
        $query = TelegramFailedMessage::where('is_resolved', false);

        if ($this->option('all')) {
            $query = TelegramFailedMessage::query();
        }

        $failed = $query->get();

        if ($failed->isEmpty()) {
            $this->info('No failed messages to retry.');
            return self::SUCCESS;
        }

        $this->info("Retrying {$failed->count()} failed messages...");
        $success = 0;
        $errors = 0;

        foreach ($failed as $message) {
            try {
                if ($message->retry()) {
                    $success++;
                } else {
                    $errors++;
                    $this->error("Failed to retry message #{$message->id}");
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error("Telegram retry failed for message #{$message->id}: {$e->getMessage()}");
            }
        }

        $this->info("✅ Retried: {$success}, Failed: {$errors}");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
