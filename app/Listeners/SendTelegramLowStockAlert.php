<?php

namespace App\Listeners;

use App\Events\LowStockAlert;
use App\Services\Telegram\TelegramAdminService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendTelegramLowStockAlert implements ShouldQueue
{
    public function __construct(
        protected TelegramAdminService $admin
    ) {}

    public function handle(LowStockAlert $event): void
    {
        $this->admin->sendLowStockNotification($event->product);
    }
}
