<?php

namespace App\Services\Telegram;

use App\Jobs\Telegram\SendTelegramMessageJob;
use App\Models\Order;
use App\Models\TelegramLink;
use App\Notifications\Telegram\OrderCancelled;
use App\Notifications\Telegram\OrderConfirmation;
use App\Notifications\Telegram\OrderDelivered;
use App\Notifications\Telegram\OrderOutForDelivery;
use App\Notifications\Telegram\OrderProcessing;
use App\Notifications\Telegram\OrderShipped;

class TelegramNotificationService
{
    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramAdminService $admin
    ) {}

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);

        $this->sendToCustomer($order, OrderConfirmation::class);
        $this->admin->sendNewOrderAlert($order, '🆕 New Order');
    }

    public function orderProcessing(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);
        $this->sendToCustomer($order, OrderProcessing::class);
    }

    public function orderShipped(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);
        $this->sendToCustomer($order, OrderShipped::class);
    }

    public function orderOutForDelivery(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);
        $this->sendToCustomer($order, OrderOutForDelivery::class);
    }

    public function orderDelivered(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);
        $this->sendToCustomer($order, OrderDelivered::class);
    }

    public function orderCancelled(Order $order): void
    {
        $order->loadMissing(['items.product', 'user']);

        $this->sendToCustomer($order, OrderCancelled::class);
        $this->admin->sendNewOrderAlert($order, '❌ Order Cancelled');
    }

    protected function sendToCustomer(Order $order, string $notificationClass): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            $notificationClass::build($order),
            $notificationClass::buttons($order)
        );
    }

    protected function getLink(int $userId): ?TelegramLink
    {
        return TelegramLink::where('user_id', $userId)
            ->whereNotNull('telegram_chat_id')
            ->where('notifications_enabled', true)
            ->first();
    }
}
