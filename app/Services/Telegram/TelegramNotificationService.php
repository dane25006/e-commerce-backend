<?php

namespace App\Services\Telegram;

use App\Jobs\SendTelegramMessageJob;
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
        protected TelegramBotService $bot
    ) {}

    public function orderPlaced(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        $text = OrderConfirmation::build($order);
        $buttons = OrderConfirmation::buttons($order);

        SendTelegramMessageJob::dispatch($link->telegram_chat_id, $text, $buttons);
    }

    public function orderProcessing(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            OrderProcessing::build($order),
            OrderProcessing::buttons($order)
        );
    }

    public function orderShipped(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            OrderShipped::build($order),
            OrderShipped::buttons($order)
        );
    }

    public function orderOutForDelivery(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            OrderOutForDelivery::build($order),
            OrderOutForDelivery::buttons($order)
        );
    }

    public function orderDelivered(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            OrderDelivered::build($order),
            OrderDelivered::buttons($order)
        );
    }

    public function orderCancelled(Order $order): void
    {
        $link = $this->getLink($order->user_id);
        if (! $link) return;

        $order->loadMissing(['items.product', 'user']);
        SendTelegramMessageJob::dispatch(
            $link->telegram_chat_id,
            OrderCancelled::build($order),
            OrderCancelled::buttons($order)
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
