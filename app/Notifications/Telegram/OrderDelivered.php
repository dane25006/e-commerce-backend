<?php

namespace App\Notifications\Telegram;

use App\Models\Order;
use App\Services\Telegram\TelegramConversationService;

class OrderDelivered
{
    public static function build(Order $order): string
    {
        $conv = app(TelegramConversationService::class);
        $orderId = $conv->formatOrderId($order);

        return "<b>🎉 Delivered, {$order->user->name}!</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Your order {$orderId} has arrived! 📬\n\n"
            . "We hope you love your purchase.\n\n"
            . "If you enjoyed the products, please leave a review ⭐ — it helps other customers too!";
    }

    public static function buttons(Order $order): array
    {
        $productId = $order->items->first()?->product_id;

        $buttons = [];
        if ($productId) {
            $buttons[] = [
                ['text' => '⭐ Leave a Review', 'url' => config('app.frontend_url') . "/products/{$productId}"],
            ];
        }
        $buttons[] = [
            ['text' => '🛍 Shop Again', 'url' => config('app.frontend_url') . '/products'],
        ];

        return $buttons;
    }
}
