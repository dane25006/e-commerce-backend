<?php

namespace App\Services\Telegram;

use App\Models\Order;
use App\Models\TelegramLink;
use Illuminate\Support\Facades\Log;

class TelegramWebhookService
{
    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramConversationService $conversation
    ) {}

    public function handle(array $update): void
    {
        try {
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text   = trim($message['text'] ?? '');
        $from   = $message['from'] ?? [];
        $username = $from['username'] ?? null;

        if (! $text) return;

        $this->bot->sendChatAction($chatId, 'typing');
        sleep(1); // Natural typing delay

        // Handle /start command (deep link verification)
        if (str_starts_with($text, '/start')) {
            $this->handleStart($chatId, $text, $username);
            return;
        }

        // Check if user is linked
        $link = TelegramLink::where('telegram_chat_id', (string) $chatId)->first();

        if (! $link || ! $link->isVerified()) {
            $this->bot->sendMessage($chatId,
                "👋 Welcome! Please connect your account first.\n\n"
                . "1. Log in to your account on our website\n"
                . "2. Go to Settings → Telegram\n"
                . "3. Click \"Connect Telegram\"\n"
                . "4. Send me the code you receive"
            );
            return;
        }

        $user = $link->user;
        $analysis = $this->conversation->understand($text);

        match ($analysis['intent']) {
            'greeting'       => $this->handleGreeting($chatId, $user),
            'thanks'         => $this->handleThanks($chatId),
            'goodbye'        => $this->handleGoodbye($chatId),
            'orders'         => $this->handleOrders($chatId, $user),
            'latest_order'   => $this->handleLatestOrder($chatId, $user),
            'track'          => $this->handleTrackOrder($chatId, $user, $analysis['order_id']),
            'order_status'   => $this->handleOrderStatus($chatId, $user, $analysis['order_id']),
            'today_orders'   => $this->handleOrdersByDate($chatId, $user, 'today'),
            'yesterday_orders' => $this->handleOrdersByDate($chatId, $user, 'yesterday'),
            'total_spent'    => $this->handleTotalSpent($chatId, $user),
            'profile'        => $this->handleProfile($chatId, $user),
            'address'        => $this->handleAddress($chatId, $user),
            'products'       => $this->handleProducts($chatId),
            'promotions'     => $this->handlePromotions($chatId),
            'support'        => $this->handleSupport($chatId, $user),
            'help'           => $this->handleHelp($chatId),
            default          => $this->handleUnknown($chatId, $user, $text),
        };
    }

    // ── CONNECTION ─────────────────────────────────────────

    protected function handleStart(int $chatId, string $text, ?string $username): void
    {
        $parts = explode(' ', $text, 2);
        $code = $parts[1] ?? null;

        if ($code) {
            $link = TelegramLink::where('verification_code', $code)->first();

            if (! $link) {
                $this->bot->sendMessage($chatId,
                    "❌ Invalid or expired code.\n\n"
                    . "Please generate a new one from your account settings."
                );
                return;
            }

            $link->update([
                'telegram_chat_id'    => (string) $chatId,
                'telegram_username'   => $username,
                'verified_at'         => now(),
                'verification_code'   => null,
                'notifications_enabled' => true,
            ]);

            $userName = $link->user->name;
            $orderCount = Order::where('user_id', $link->user_id)->count();
            $latestOrder = Order::where('user_id', $link->user_id)->latest()->first();

            $welcome = "👋 <b>Hello, {$userName}!</b>\n\n"
                . "✅ Your Telegram has been connected successfully!\n\n"
                . "You will now receive live updates about your orders.";

            if ($orderCount > 0) {
                $welcome .= "\n\n📦 You have <b>{$orderCount}</b> order" . ($orderCount > 1 ? 's' : '') . " with us.";
            }

            $welcome .= "\n\n<b>Try asking me:</b>\n"
                . "• \"My Orders\" — View your orders\n"
                . "• \"Track my order\" — Check shipping\n"
                . "• \"My Profile\" — Your account info\n"
                . "• \"Help\" — See all commands";

            $buttons = [];
            if ($latestOrder) {
                $buttons[] = [
                    ['text' => '📦 My Orders', 'callback_data' => 'orders'],
                    ['text' => '🚚 Track Latest', 'callback_data' => "order_{$latestOrder->id}"],
                ];
            }
            $buttons[] = [
                ['text' => '👤 My Profile', 'callback_data' => 'profile'],
                ['text' => '❓ Help', 'callback_data' => 'help'],
            ];

            $this->bot->sendMessage($chatId, $welcome, $buttons);
        } else {
            $this->bot->sendMessage($chatId,
                "👋 <b>Welcome to Scentique Bot!</b>\n\n"
                . "To connect your account:\n"
                . "1. Go to our website → Settings → Telegram\n"
                . "2. Click \"Connect Telegram\"\n"
                . "3. You'll receive a link — tap it to open me\n\n"
                . "Already have a code? Send: <code>/start YOUR_CODE</code>"
            );
        }
    }

    // ── GREETING ───────────────────────────────────────────

    protected function handleGreeting(int $chatId, $user): void
    {
        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'out_for_delivery'])
            ->count();

        $shippedCount = Order::where('user_id', $user->id)
            ->where('status', 'shipped')
            ->count();

        $greeting = "Hello {$user->name} 👋\n\nWelcome back!";

        if ($activeOrders > 0) {
            $greeting .= "\n\n📦 You have <b>{$activeOrders}</b> active order" . ($activeOrders > 1 ? 's' : '') . ".";
        }
        if ($shippedCount > 0) {
            $greeting .= "\n🚚 <b>{$shippedCount}</b> order" . ($shippedCount > 1 ? 's are' : ' is') . " being shipped.";
        }

        $greeting .= "\n\nHow can I help you today?";

        $buttons = [
            [
                ['text' => '📦 My Orders', 'callback_data' => 'orders'],
                ['text' => '🚚 Track', 'callback_data' => 'track_latest'],
            ],
            [
                ['text' => '👤 My Profile', 'callback_data' => 'profile'],
                ['text' => '❓ Help', 'callback_data' => 'help'],
            ],
        ];

        $this->bot->sendMessage($chatId, $greeting, $buttons);
    }

    protected function handleThanks(int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "😊 You're very welcome! I'm happy to help.\n\n"
            . "If you need anything else, just let me know!"
        );
    }

    protected function handleGoodbye(int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "👋 Take care! Come back anytime.\n\n"
            . "You'll still receive automatic updates about your orders here."
        );
    }

    // ── ORDERS ─────────────────────────────────────────────

    protected function handleOrders(int $chatId, $user): void
    {
        $orders = Order::where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->take(5)
            ->get();

        if ($orders->isEmpty()) {
            $this->bot->sendMessage($chatId,
                "📭 You haven't placed any orders yet.\n\n"
                . "🛍 <b>Ready to start shopping?</b>",
                [
                    [['text' => '🛍 Visit Our Store', 'url' => config('app.frontend_url') . '/products']],
                ]
            );
            return;
        }

        $text = "<b>📦 Your Recent Orders</b>\n"
              . "━━━━━━━━━━━━━━━\n\n";

        $i = 0;
        $buttons = [];
        foreach ($orders as $order) {
            $i++;
            $text .= "<b>{$i}.</b> {$this->conversation->buildOrderCard($order)}\n\n";
            $buttons[] = [
                ['text' => "📦 Order {$this->conversation->formatOrderId($order)}", 'callback_data' => "order_{$order->id}"],
            ];
        }

        $buttons[] = [
            ['text' => '🛍 Visit Store', 'url' => config('app.frontend_url') . '/products'],
        ];

        $this->bot->sendMessage($chatId, $text, $buttons);
    }

    protected function handleLatestOrder(int $chatId, $user): void
    {
        $order = Order::where('user_id', $user->id)
            ->with('items.product')
            ->latest()
            ->first();

        if (! $order) {
            $this->bot->sendMessage($chatId, "📭 You don't have any orders yet.");
            return;
        }

        $text = $this->conversation->buildOrderSummary($order);

        $this->bot->sendMessage($chatId, $text, $this->orderButtons($order));
    }

    protected function handleTrackOrder(int $chatId, $user, ?int $orderId): void
    {
        $query = Order::where('user_id', $user->id)->with('items.product');

        if ($orderId) {
            $query->where('id', $orderId);
        } else {
            $query->whereIn('status', ['shipped', 'out_for_delivery'])->latest();
        }

        $order = $query->first();

        if (! $order) {
            $this->bot->sendMessage($chatId,
                $orderId
                    ? "❌ Order #{$orderId} not found or it hasn't shipped yet."
                    : "📭 You don't have any orders in transit right now."
            );
            return;
        }

        $orderId = $this->conversation->formatOrderId($order);
        $status = $this->conversation->formatStatus($order->status);

        $text = "<b>🚚 Order {$orderId}</b>\n"
              . "━━━━━━━━━━━━━━━\n"
              . "Status: {$status}\n"
              . "📅 Last updated: {$order->updated_at->format('M d, Y H:i')}\n";

        if (in_array($order->status, ['shipped', 'out_for_delivery'])) {
            $text .= "\n📍 <b>Your package is on its way!</b>";
            if ($order->status === 'out_for_delivery') {
                $text .= "\n📬 Expected delivery today!";
            }
        } elseif ($order->status === 'delivered') {
            $text .= "\n✅ <b>Delivered!</b> Enjoy your purchase!";
        } elseif ($order->status === 'processing') {
            $text .= "\n📦 <b>Being prepared</b> — we'll notify you when it ships.";
        } elseif ($order->status === 'pending') {
            $text .= "\n⏳ <b>Pending</b> — we'll start processing it soon.";
        }

        $this->bot->sendMessage($chatId, $text, $this->orderButtons($order));
    }

    protected function handleOrderStatus(int $chatId, $user, ?int $orderId): void
    {
        $query = Order::where('user_id', $user->id);

        if ($orderId) {
            $query->where('id', $orderId);
        } else {
            $query->latest();
        }

        $order = $query->first();

        if (! $order) {
            $this->bot->sendMessage($chatId, "❌ Order not found.");
            return;
        }

        $this->bot->sendMessage($chatId,
            "<b>📊 Order {$this->conversation->formatOrderId($order)}</b>\n"
            . "Status: {$this->conversation->formatStatus($order->status)}\n"
            . "Updated: {$order->updated_at->format('M d, Y H:i')}",
            $this->orderButtons($order)
        );
    }

    protected function handleOrdersByDate(int $chatId, $user, string $period): void
    {
        $query = Order::where('user_id', $user->id);

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } else {
            $query->whereDate('created_at', today()->subDay());
        }

        $orders = $query->with('items.product')->latest()->get();

        if ($orders->isEmpty()) {
            $this->bot->sendMessage($chatId,
                $period === 'today'
                    ? "📭 You don't have any orders placed today."
                    : "📭 You didn't place any orders yesterday."
            );
            return;
        }

        $label = $period === 'today' ? "Today's" : "Yesterday's";
        $text = "<b>📦 {$label} Orders</b>\n━━━━━━━━━━━━━━━\n\n";

        $buttons = [];
        foreach ($orders as $order) {
            $text .= "• {$this->conversation->formatOrderId($order)} — {$this->conversation->formatStatus($order->status)} — \${$order->total}\n";
            $buttons[] = [
                ['text' => "📦 {$this->conversation->formatOrderId($order)}", 'callback_data' => "order_{$order->id}"],
            ];
        }

        $this->bot->sendMessage($chatId, $text, $buttons);
    }

    protected function handleTotalSpent(int $chatId, $user): void
    {
        $total = Order::where('user_id', $user->id)->sum('total');
        $count = Order::where('user_id', $user->id)->count();

        $this->bot->sendMessage($chatId,
            "💰 <b>Your Spending</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Total spent: <b>\${$total}</b>\n"
            . "Orders placed: <b>{$count}</b>\n\n"
            . "Thank you for being a valued customer! 🎉"
        );
    }

    // ── PROFILE ────────────────────────────────────────────

    protected function handleProfile(int $chatId, $user): void
    {
        $link = TelegramLink::where('user_id', $user->id)->first();
        $orderCount = Order::where('user_id', $user->id)->count();
        $connectedDate = $link?->verified_at?->format('M d, Y') ?? 'N/A';

        $this->bot->sendMessage($chatId,
            "👤 <b>My Profile</b>\n"
            . "━━━━━━━━━━━━━━━\n"
            . "Name: {$user->name}\n"
            . "Email: {$user->email}\n"
            . "📦 Orders: {$orderCount}\n"
            . "🔔 Notifications: " . ($link?->notifications_enabled ? '✅ Enabled' : '❌ Disabled') . "\n"
            . "📅 Connected: {$connectedDate}"
        );
    }

    protected function handleAddress(int $chatId, $user): void
    {
        $latestOrder = Order::where('user_id', $user->id)->latest()->first();

        if ($latestOrder && $latestOrder->shipping_address) {
            $this->bot->sendMessage($chatId,
                "📬 <b>Your Shipping Address</b>\n"
                . "━━━━━━━━━━━━━━━\n"
                . "{$latestOrder->shipping_address}\n\n"
                . "(From your most recent order)"
            );
        } else {
            $this->bot->sendMessage($chatId,
                "📬 You don't have a saved address yet.\n\n"
                . "Your shipping address will be available after your first order."
            );
        }
    }

    // ── HELP & SUPPORT ─────────────────────────────────────

    protected function handleHelp(int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "<b>🤖 I can help you with:</b>\n"
            . "━━━━━━━━━━━━━━━\n\n"
            . "🗣 <b>Try saying:</b>\n"
            . "• \"Hi\" — Greeting with your order summary\n"
            . "• \"My Orders\" — View recent orders\n"
            . "• \"Where is my order?\" — Track shipping\n"
            . "• \"How much did I pay?\" — Total spent\n"
            . "• \"My Profile\" — Account info\n\n"
            . "📋 <b>Commands:</b>\n"
            . "/start — Connect your account\n"
            . "/help — This message\n"
            . "/support — Contact customer service"
        );
    }

    protected function handleSupport(int $chatId, $user): void
    {
        $this->bot->sendMessage($chatId,
            "📞 <b>Need help?</b>\n\n"
            . "Our support team is here for you!\n\n"
            . "📧 Email: support@scentique.com\n"
            . "🌐 Website: " . config('app.frontend_url') . "/contact\n\n"
            . "We typically respond within 24 hours.\n\n"
            . "For urgent matters, please call us during business hours.",
            [
                [['text' => '🌐 Contact Us', 'url' => config('app.frontend_url') . '/contact']],
            ]
        );
    }

    protected function handleProducts(int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "🛍 <b>Explore Our Collection</b>\n\n"
            . "Discover luxury fragrances for every occasion.",
            [
                [
                    ['text' => '🛍 All Products', 'url' => config('app.frontend_url') . '/products'],
                    ['text' => '✨ New Arrivals', 'url' => config('app.frontend_url') . '/new-arrivals'],
                ],
            ]
        );
    }

    protected function handlePromotions(int $chatId): void
    {
        $this->bot->sendMessage($chatId,
            "🎉 <b>Current Offers</b>\n\n"
            . "Check our website for the latest promotions and discounts!",
            [
                [['text' => '🎉 View Deals', 'url' => config('app.frontend_url') . '/products']],
            ]
        );
    }

    protected function handleUnknown(int $chatId, $user, string $text): void
    {
        $this->bot->sendMessage($chatId,
            "I'm not sure I understand \"<i>" . e($text) . "</i>\".\n\n"
            . "But don't worry! Here's what I can help with:\n\n"
            . "• 📦 <b>My Orders</b> — View your orders\n"
            . "• 🚚 <b>Track</b> — Where's my package?\n"
            . "• 👤 <b>My Profile</b> — Account info\n"
            . "• ❓ <b>Help</b> — See all options\n\n"
            . "Just type naturally and I'll do my best! 🙂",
            [
                [
                    ['text' => '📦 My Orders', 'callback_data' => 'orders'],
                    ['text' => '👤 My Profile', 'callback_data' => 'profile'],
                ],
                [
                    ['text' => '❓ Help', 'callback_data' => 'help'],
                ],
            ]
        );
    }

    // ── CALLBACK QUERIES ───────────────────────────────────

    protected function handleCallbackQuery(array $callback): void
    {
        $data = $callback['data'] ?? '';
        $callbackId = $callback['id'] ?? '';
        $chatId = $callback['message']['chat']['id'] ?? null;

        if (! $chatId) return;

        $link = TelegramLink::where('telegram_chat_id', (string) $chatId)->first();
        if (! $link || ! $link->isVerified()) {
            $this->bot->answerCallbackQuery($callbackId, 'Please connect your account first');
            return;
        }

        $this->bot->answerCallbackQuery($callbackId);

        if ($data === 'orders') {
            $this->handleOrders($chatId, $link->user);
        } elseif ($data === 'profile') {
            $this->handleProfile($chatId, $link->user);
        } elseif ($data === 'help') {
            $this->handleHelp($chatId);
        } elseif ($data === 'track_latest') {
            $order = Order::where('user_id', $link->user_id)
                ->whereIn('status', ['shipped', 'out_for_delivery', 'processing'])
                ->latest()
                ->first();
            if ($order) {
                $this->handleTrackOrder($chatId, $link->user, $order->id);
            } else {
                $this->bot->sendMessage($chatId, "📭 You don't have any orders in transit.");
            }
        } elseif (str_starts_with($data, 'order_')) {
            $orderId = (int) substr($data, 6);
            $order = Order::where('id', $orderId)
                ->where('user_id', $link->user_id)
                ->with('items.product')
                ->first();
            if ($order) {
                $text = $this->conversation->buildOrderSummary($order);
                $this->bot->sendMessage($chatId, $text, $this->orderButtons($order));
            } else {
                $this->bot->sendMessage($chatId, "❌ Order not found.");
            }
        }
    }

    // ── SHARED ─────────────────────────────────────────────

    protected function orderButtons(Order $order): array
    {
        return [
            [
                ['text' => '📦 View Online', 'url' => config('app.frontend_url') . "/orders/{$order->id}"],
            ],
            [
                ['text' => '📞 Contact Support', 'callback_data' => 'support'],
            ],
        ];
    }
}
