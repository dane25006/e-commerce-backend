<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderProcessing
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);

        return "<b>📦 Good news, {$order->user->name}!</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order {$orderId} is now being <b>prepared</b>.\n\n"
            . "Our team is carefully packing your items.\n"
            . "We'll notify you as soon as it ships! 🚀";
    }

    public static function buttons(Order $order): array
    {
        return [
            [
                ['text' => '📦 View Order', 'url' => config('app.frontend_url') . "/orders/{$order->id}"],
            ],
        ];
    }
}
