<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderConfirmation
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);
        $date = $conv->formatDate($order->created_at);
        $time = $conv->formatTime($order->created_at);

        $items = $order->items->map(fn($i) =>
            "• {$i->product?->name} ×{$i->quantity} — \${$i->price}"
        )->implode("\n");

        $paymentMethod = match ($order->payment_method) {
            'cash_on_delivery' => 'Cash on Delivery',
            'credit_card'      => 'Credit Card',
            'paypal'           => 'PayPal',
            default            => ucfirst(str_replace('_', ' ', $order->payment_method)),
        };

        $userName = $order->user->name;

        return "🛍 <b>Thank you for your purchase, {$userName}!</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order has been placed successfully.\n\n"
            . "<b>Order Number:</b>\n{$orderId}\n\n"
            . "<b>Order Date:</b>\n{$date}\n\n"
            . "<b>Order Time:</b>\n{$time}\n\n"
            . "<b>Items</b>\n{$items}\n\n"
            . "<b>Total:</b>\n\${$order->total}\n\n"
            . "<b>Payment:</b>\n{$paymentMethod}\n\n"
            . "<b>Status:</b>\n⏳ Pending\n\n"
            . "<b>Estimated Delivery:</b>\n" . $order->created_at->addDays(5)->format('j F Y') . "\n\n"
            . "We'll notify you when your order ships! 🚚";
    }

    public static function buttons(Order $order): array
    {
        return [
            [
                ['text' => '📦 View My Orders', 'url' => config('app.frontend_url') . "/orders/{$order->id}"],
                ['text' => '🚚 Track Order', 'callback_data' => "order_{$order->id}"],
            ],
            [
                ['text' => '☎ Contact Support', 'callback_data' => 'support'],
            ],
        ];
    }
}
