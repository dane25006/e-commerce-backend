<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Services\Telegram\TelegramNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendTelegramOrderNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public int $delay = 2;

    public function __construct(
        protected TelegramNotificationService $telegram
    ) {}

    public function handleOrderPlaced(OrderPlaced $event): void
    {
        $this->telegram->orderPlaced($event->order);
    }

    public function handleOrderStatusUpdated(OrderStatusUpdated $event): void
    {
        $status = $event->newStatus;

        match ($status) {
            'processing' => $this->telegram->orderProcessing($event->order),
            'shipped'    => $this->telegram->orderShipped($event->order),
            'out_for_delivery' => $this->telegram->orderOutForDelivery($event->order),
            'delivered'  => $this->telegram->orderDelivered($event->order),
            'cancelled'  => $this->telegram->orderCancelled($event->order),
            default      => null,
        };
    }
}
