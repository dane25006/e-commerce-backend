<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\TelegramService;

class OrderObserver
{
    protected array $statusLabels = [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'completed'  => 'Completed',
        'cancelled'  => 'Cancelled',
    ];

    public function updated(Order $order, TelegramService $telegram): void
    {
        if ($order->wasChanged('status')) {
            $label = $this->statusLabels[$order->status] ?? ucfirst($order->status);
            $telegram->sendOrderNotification($order, $label);
        }
    }
}
