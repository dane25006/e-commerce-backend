<?php

namespace App\Services\Telegram;

use App\Models\Order;
use App\Models\TelegramLink;
use Illuminate\Support\Str;

class TelegramConversationService
{
    /** Intent definitions with keyword patterns */
    protected array $intents = [
        'greeting'   => ['hi', 'hello', 'hey', 'yo', 'sup', 'good morning', 'good evening', 'good afternoon'],
        'thanks'     => ['thanks', 'thank you', 'thx', 'ty', 'appreciate', 'thank'],
        'orders'     => ['my orders', 'my order', 'orders', 'order list', 'show orders', 'list orders', 'recent orders'],
        'latest_order' => ['latest order', 'last order', 'most recent order', 'recent order'],
        'track'      => ['track', 'tracking', 'where is my', "where's my", 'shipping status', 'arriving', 'delivery status'],
        'order_status' => ['order status', 'status of my order', 'is my order', 'order update'],
        'today_orders' => ["today's orders", 'today order', 'ordered today'],
        'yesterday_orders' => ["yesterday's orders", 'yesterday order'],
        'total_spent' => ['how much', 'total spent', 'paid', 'how much did i pay', 'total amount', 'spent'],
        'profile'    => ['my profile', 'profile', 'my account', 'account info', 'who am i'],
        'address'    => ['my address', 'shipping address', 'delivery address', 'my shipping', 'address'],
        'support'    => ['support', 'contact', 'help me', 'talk to someone', 'customer service', 'agent', 'speak to'],
        'help'       => ['help', 'commands', 'what can you do', 'guide', 'tutorial'],
        'products'   => ['products', 'shop', 'browse', 'catalog', 'new arrivals', 'latest products', 'what do you sell'],
        'promotions' => ['promotions', 'discount', 'sale', 'deal', 'coupon', 'offer', 'promo'],
        'goodbye'    => ['bye', 'goodbye', 'see you', 'later', 'cya', 'take care'],
    ];

    /** @return array{intent: string, confidence: float, order_id: int|null} */
    public function understand(string $text): array
    {
        $text = strtolower(trim($text));
        $results = [];

        foreach ($this->intents as $intent => $patterns) {
            $score = 0;
            foreach ($patterns as $pattern) {
                if (str_contains($text, $pattern)) {
                    $score = max($score, $this->calculateSimilarity($text, $pattern));
                }
            }

            // Exact match boost
            if (in_array($text, $patterns)) {
                $score = 1.0;
            }

            if ($score > 0) {
                $results[] = ['intent' => $intent, 'confidence' => $score];
            }
        }

        // Check for order number references
        $orderId = null;
        if (preg_match('/\b(\d{4,6})\b/', $text, $m)) {
            $orderId = (int) $m[1];
        } elseif (preg_match('/order\s*#?\s*(\d+)/i', $text, $m)) {
            $orderId = (int) $m[1];
        }

        usort($results, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return [
            'intent'     => $results[0]['intent'] ?? 'unknown',
            'confidence' => $results[0]['confidence'] ?? 0,
            'order_id'   => $orderId,
        ];
    }

    public function formatOrderId(Order $order): string
    {
        return '#ORD-' . str_pad((string) $order->id, 7, '0', STR_PAD_LEFT);
    }

    public function formatDate(\DateTimeInterface $date): string
    {
        return $date->format('j F Y');
    }

    public function formatTime(\DateTimeInterface $date): string
    {
        return $date->format('g:i A');
    }

    public function formatStatus(string $status): string
    {
        return match ($status) {
            'pending'         => '⏳ Pending',
            'processing'      => '🔄 Processing',
            'shipped'         => '🚚 Shipped',
            'out_for_delivery' => '📍 Out for Delivery',
            'delivered'       => '✅ Delivered',
            'cancelled'       => '❌ Cancelled',
            'refunded'        => '💰 Refunded',
            default           => ucfirst($status),
        };
    }

    public function buildOrderSummary(Order $order): string
    {
        $orderId = $this->formatOrderId($order);
        $date = $this->formatDate($order->created_at);
        $time = $this->formatTime($order->created_at);
        $status = $this->formatStatus($order->status);

        $items = $order->items->map(fn($i) =>
            "• {$i->product?->name} ×{$i->quantity} — \${$i->price}"
        )->implode("\n");

        $paymentMethod = match ($order->payment_method) {
            'cash_on_delivery' => 'Cash on Delivery',
            'credit_card'      => 'Credit Card',
            'paypal'           => 'PayPal',
            default            => ucfirst(str_replace('_', ' ', $order->payment_method)),
        };

        return "<b>🛍 Order {$orderId}</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "📅 Date: {$date}\n"
            . "⏰ Time: {$time}\n"
            . "📊 Status: {$status}\n"
            . "💰 Total: <b>\${$order->total}</b>\n"
            . "💳 Payment: {$paymentMethod}\n"
            . "📬 Ship to: {$order->shipping_address}\n\n"
            . "<b>Items:</b>\n{$items}";
    }

    public function buildOrderCard(Order $order): string
    {
        $orderId = $this->formatOrderId($order);
        $date = $this->formatDate($order->created_at);
        $status = $this->formatStatus($order->status);

        return "{$orderId}\n"
            . "📅 {$date}\n"
            . "📊 {$status}\n"
            . "💰 \${$order->total}";
    }

    protected function calculateSimilarity(string $text, string $pattern): float
    {
        $words = explode(' ', $pattern);
        $matched = 0;

        foreach ($words as $word) {
            if (str_contains($text, $word)) {
                $matched++;
            }
        }

        return count($words) > 0 ? $matched / count($words) : 0;
    }
}
