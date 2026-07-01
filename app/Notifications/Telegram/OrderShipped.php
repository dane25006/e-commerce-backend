<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderShipped
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);

        return "<b>📦 Good news, {$order->user->name}!</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order {$orderId} has been <b>shipped</b>! 🚚\n\n"
            . "<b>Courier:</b>\nStandard Shipping\n\n"
            . "<b>Estimated Arrival:</b>\n" . now()->addDays(3)->format('j F Y') . "\n\n"
            . "You'll receive another notification when it's out for delivery.";
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
