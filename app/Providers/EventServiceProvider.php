<?php

namespace App\Providers;

use App\Events\LowStockAlert;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Events\PaymentApproved;
use App\Events\PaymentReceived;
use App\Events\PaymentRejected;
use App\Listeners\SendTelegramLowStockAlert;
use App\Listeners\SendTelegramOrderNotification;
use App\Listeners\SendTelegramPaymentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPlaced::class => [
            SendTelegramOrderNotification::class,
        ],
        OrderStatusUpdated::class => [
            SendTelegramOrderNotification::class,
        ],
        PaymentReceived::class => [
            SendTelegramPaymentNotification::class,
        ],
        PaymentApproved::class => [
            SendTelegramPaymentNotification::class,
        ],
        PaymentRejected::class => [
            SendTelegramPaymentNotification::class,
        ],
        LowStockAlert::class => [
            SendTelegramLowStockAlert::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
