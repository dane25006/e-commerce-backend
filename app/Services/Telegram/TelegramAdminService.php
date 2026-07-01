<?php

namespace App\Services\Telegram;

use App\Models\Order;
use App\Models\Product;
use App\Models\TelegramAdminChat;
use App\Jobs\Telegram\SendTelegramMessageJob;
use App\Events\OrderStatusUpdated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramAdminService
{
    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramLogService $logger
    ) {}

    // ── DASHBOARD ────────────────────────────────────────

    public function sendDashboard(int $chatId): void
    {
        $ordersToday = Order::whereDate('created_at', today())->count();
        $revenueToday = Order::whereDate('created_at', today())->sum('total');
        $pendingOrders = Order::where('status', 'pending')->count();
        $toShip = Order::where('status', 'processing')->count();
        $lowStock = Product::where('stock', '<', 5)->count();
        $pendingPayments = Order::where('status', 'pending')
            ->where('payment_method', '!=', 'cash_on_delivery')->count();

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    📊 <b>ADMIN DASHBOARD</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "📦 <b>Orders Today:</b>      {$ordersToday}\n"
            . "💰 <b>Revenue Today:</b>     \${$revenueToday}\n"
            . "⏳ <b>Pending Orders:</b>    {$pendingOrders}\n"
            . "🚚 <b>To Ship:</b>           {$toShip}\n"
            . "⚠️ <b>Low Stock Items:</b>   {$lowStock}\n"
            . "💳 <b>Pending Payments:</b>  {$pendingPayments}\n";

        $buttons = [
            [
                ['text' => '📦 Orders', 'callback_data' => 'admin_orders_page_1'],
                ['text' => '⚠️ Low Stock', 'callback_data' => 'admin_low_stock'],
            ],
            [
                ['text' => '📊 Reports', 'callback_data' => 'admin_reports'],
                ['text' => '🔄 Refresh', 'callback_data' => 'admin_dashboard'],
            ],
        ];

        $this->bot->sendMessage($chatId, $text, $buttons);
    }

    // ── ORDERS ───────────────────────────────────────────

    public function sendOrdersList(int $chatId, int $page = 1): void
    {
        $orders = Order::with('user')
            ->latest()
            ->paginate(5, ['*'], 'page', $page);

        if ($orders->isEmpty()) {
            $this->bot->sendMessage($chatId, "📭 No orders found.");
            return;
        }

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    📦 <b>ORDERS (Page {$orders->currentPage()}/{$orders->lastPage()})</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $buttons = [];

        foreach ($orders as $order) {
            $statusEmoji = match ($order->status) {
                'pending' => '⏳', 'processing' => '🔄', 'shipped' => '🚚',
                'out_for_delivery' => '📍', 'delivered' => '✅', 'cancelled' => '❌',
                default => '📦',
            };
            $text .= "{$statusEmoji} <b>#{$order->id}</b> — {$order->user->name} — \${$order->total}\n";

            $actionButtons = [];
            if ($order->status === 'pending') {
                $actionButtons = [
                    ['text' => '✅ Accept', 'callback_data' => "admin_accept_order_{$order->id}"],
                    ['text' => '❌ Reject', 'callback_data' => "admin_reject_order_{$order->id}"],
                ];
            } elseif ($order->status === 'processing') {
                $actionButtons = [
                    ['text' => '🚚 Ship', 'callback_data' => "admin_ship_order_{$order->id}"],
                ];
            } elseif ($order->status === 'shipped') {
                $actionButtons = [
                    ['text' => '✅ Delivered', 'callback_data' => "admin_deliver_order_{$order->id}"],
                ];
            }

            if ($actionButtons) {
                $buttons[] = $actionButtons;
            }
        }

        $navButtons = [];
        if ($orders->previousPageUrl()) {
            $navButtons[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_orders_page_' . ($page - 1)];
        }
        if ($orders->nextPageUrl()) {
            $navButtons[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_orders_page_' . ($page + 1)];
        }
        if ($navButtons) {
            $buttons[] = $navButtons;
        }

        $buttons[] = [['text' => '🔄 Refresh', 'callback_data' => 'admin_dashboard']];

        $this->bot->sendMessage($chatId, $text, $buttons);
    }

    // ── ORDER ACTIONS ────────────────────────────────────

    public function acceptOrder(int $chatId, int $orderId): void
    {
        DB::transaction(function () use ($chatId, $orderId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                $this->bot->sendMessage($chatId, "⚠️ Order #{$orderId} is already {$order->status}.");
                return;
            }

            $oldStatus = $order->status;
            $order->update(['status' => 'processing']);

            OrderStatusUpdated::dispatch($order, $oldStatus, 'processing');

            $this->bot->sendMessage($chatId, "✅ Order #{$orderId} accepted and now processing.");
        });
    }

    public function rejectOrder(int $chatId, int $orderId): void
    {
        DB::transaction(function () use ($chatId, $orderId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);

            if ($order->status !== 'pending') {
                $this->bot->sendMessage($chatId, "⚠️ Order #{$orderId} is already {$order->status}.");
                return;
            }

            $oldStatus = $order->status;

            // Restore stock
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
            OrderStatusUpdated::dispatch($order, $oldStatus, 'cancelled');

            $this->bot->sendMessage($chatId, "❌ Order #{$orderId} rejected and cancelled.");
        });
    }

    public function updateStatus(int $chatId, int $orderId, string $newStatus): void
    {
        DB::transaction(function () use ($chatId, $orderId, $newStatus) {
            $order = Order::lockForUpdate()->findOrFail($orderId);
            $oldStatus = $order->status;

            $allowed = match ($oldStatus) {
                'processing' => ['shipped'],
                'shipped'    => ['out_for_delivery', 'delivered'],
                'out_for_delivery' => ['delivered'],
                default      => [],
            };

            if (! in_array($newStatus, $allowed)) {
                $this->bot->sendMessage($chatId, "⚠️ Cannot change from {$oldStatus} to {$newStatus}.");
                return;
            }

            $order->update(['status' => $newStatus]);
            OrderStatusUpdated::dispatch($order, $oldStatus, $newStatus);

            $emoji = match ($newStatus) {
                'shipped' => '🚚', 'out_for_delivery' => '📍', 'delivered' => '✅', default => '📦'
            };
            $this->bot->sendMessage($chatId, "{$emoji} Order #{$orderId} → {$newStatus}.");
        });
    }

    // ── LOW STOCK ────────────────────────────────────────

    public function sendLowStockAlert(int $chatId): void
    {
        $products = Product::where('stock', '<', 5)->orderBy('stock')->get();

        if ($products->isEmpty()) {
            $this->bot->sendMessage($chatId, "✅ All products are well-stocked!");
            return;
        }

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ⚠️ <b>LOW STOCK ALERTS</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        $buttons = [];

        foreach ($products as $product) {
            $text .= "• <b>{$product->name}</b> — Stock: {$product->stock}\n";
            $buttons[] = [
                ['text' => "➕ {$product->name} +10", 'callback_data' => "admin_restock_{$product->id}_10"],
                ['text' => "➕ +20", 'callback_data' => "admin_restock_{$product->id}_20"],
            ];
        }

        $this->bot->sendMessage($chatId, $text, $buttons);
    }

    public function restockProduct(int $chatId, int $productId, int $quantity): void
    {
        $product = Product::find($productId);

        if (! $product) {
            $this->bot->sendMessage($chatId, "❌ Product not found.");
            return;
        }

        $product->increment('stock', $quantity);

        $this->bot->sendMessage($chatId,
            "✅ <b>{$product->name}</b> restocked +{$quantity} (now {$product->stock})"
        );
    }

    // ── REPORTS ──────────────────────────────────────────

    public function sendDailyReport(int $chatId, ?string $date = null): void
    {
        $date = $date ? now()->parse($date) : today();

        $orders = Order::whereDate('created_at', $date);
        $totalOrders = $orders->count();
        $revenue = $orders->sum('total');
        $newCustomers = \App\Models\User::whereDate('created_at', $date)->count();

        $topProducts = \App\Models\OrderItem::selectRaw('product_id, SUM(quantity) as total_qty')
            ->whereHas('order', fn($q) => $q->whereDate('created_at', $date))
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(3)
            ->get()
            ->map(fn($item) => $item->product?->name ?? 'Deleted Product');

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    📊 <b>DAILY REPORT</b>\n"
            . "    {$date->format('M d, Y')}\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "💰 <b>Revenue:</b>       \${$revenue}\n"
            . "📦 <b>Orders:</b>         {$totalOrders}\n"
            . "👤 <b>New Customers:</b>  {$newCustomers}\n"
            . ($totalOrders > 0 ? "⭐ <b>Avg Order:</b>     \$" . round($revenue / $totalOrders, 2) . "\n" : "")
            . "\n"
            . "<b>🏆 Top Products:</b>\n";

        if ($topProducts->isNotEmpty()) {
            foreach ($topProducts as $i => $name) {
                $text .= ($i + 1) . ". {$name}\n";
            }
        } else {
            $text .= "No sales today.\n";
        }

        $this->bot->sendMessage($chatId, $text, [
            [['text' => '🔄 Refresh', 'callback_data' => 'admin_reports']],
        ]);
    }

    // ── NEW ORDER ALERT ──────────────────────────────────

    public function sendNewOrderAlert(Order $order, string $title = '🆕 New Order'): void
    {
        $adminChats = TelegramAdminChat::active()->where('notify_orders', true)->get();

        if ($adminChats->isEmpty() && config('telegram.admin_chat_id')) {
            $this->sendNewOrderAlertToChat(config('telegram.admin_chat_id'), $order, $title);
            return;
        }

        foreach ($adminChats as $admin) {
            $this->sendNewOrderAlertToChat($admin->chat_id, $order, $title);
        }
    }

    protected function sendNewOrderAlertToChat(string|int $chatId, Order $order, string $title = '🆕 New Order'): void
    {
        $order->loadMissing(['items.product', 'user']);
        $itemsList = $order->items->map(fn($i) =>
            "• {$i->product?->name} ×{$i->quantity} — \${$i->price}"
        )->implode("\n");

        $paymentLabel = match ($order->payment_method) {
            'cash_on_delivery' => 'Cash on Delivery',
            'credit_card'      => 'Credit Card',
            'paypal'           => 'PayPal',
            default            => ucfirst(str_replace('_', ' ', $order->payment_method)),
        };

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    {$title} <b>#{$order->id}</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👤 <b>{$order->user->name}</b>\n"
            . "📧 {$order->user->email}\n"
            . "💳 {$paymentLabel}\n"
            . "💰 \${$order->total}\n\n"
            . "<b>Items:</b>\n{$itemsList}\n\n"
            . "📬 {$order->shipping_address}\n"
            . "📅 {$order->created_at->format('M d, Y H:i')}";

        $buttons = [
            [
                ['text' => '✅ Accept', 'callback_data' => "admin_accept_order_{$order->id}"],
                ['text' => '❌ Reject', 'callback_data' => "admin_reject_order_{$order->id}"],
            ],
        ];

        SendTelegramMessageJob::dispatch($chatId, $text, $buttons);
    }

    // ── LOW STOCK NOTIFICATION ───────────────────────────

    public function sendLowStockNotification(Product $product): void
    {
        $adminChats = TelegramAdminChat::active()->where('notify_stock', true)->get();

        $chatIds = $adminChats->pluck('chat_id')->toArray();
        if (empty($chatIds) && config('telegram.admin_chat_id')) {
            $chatIds = [config('telegram.admin_chat_id')];
        }

        if (empty($chatIds)) return;

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ⚠️ <b>LOW STOCK ALERT</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "<b>Product:</b> {$product->name}\n"
            . "<b>Current Stock:</b> {$product->stock}\n"
            . "<b>Category:</b> {$product->category?->name}\n";

        $buttons = [
            [
                ['text' => "➕ +10", 'callback_data' => "admin_restock_{$product->id}_10"],
                ['text' => "➕ +20", 'callback_data' => "admin_restock_{$product->id}_20"],
            ],
        ];

        foreach ($chatIds as $chatId) {
            SendTelegramMessageJob::dispatch($chatId, $text, $buttons);
        }
    }
}
