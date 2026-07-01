<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderCancelled
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);

        $message = "<b>❌ Order Cancelled, {$order->user->name}</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order {$orderId} has been cancelled.\n";

        if ((float) $order->total > 0) {
            $message .= "\n💰 A refund of <b>\${$order->total}</b> will be processed within 5–7 business days and returned to your original payment method.";
        }

        $message .= "\n\nWe're sorry to see you go. If you need help, please contact our support team.";

        return $message;
    }

    public static function buttons(Order $order): array
    {
        return [
            [
                ['text' => '🛍 Continue Shopping', 'url' => config('app.frontend_url') . '/products'],
                ['text' => '📞 Contact Support', 'callback_data' => 'support'],
            ],
        ];
    }
}
