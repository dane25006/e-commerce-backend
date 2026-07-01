<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderOutForDelivery
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);

        return "<b>📍 On its way, {$order->user->name}!</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order {$orderId} is <b>out for delivery</b> today! 🚚\n\n"
            . "📬 Please make sure someone is available to receive the package.\n\n"
            . "Estimated arrival: <b>Today</b>";
    }

    public static function buttons(Order $order): array
    {
        return [
            [
                ['text' => '📍 Track Order', 'url' => config('app.frontend_url') . "/orders/{$order->id}"],
            ],
        ];
    }
}
