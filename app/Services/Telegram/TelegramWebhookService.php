<?php

namespace App\Services\Telegram;

use App\Events\OrderStatusUpdated;
use App\Models\BotSetting;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TelegramAdminChat;
use App\Models\TelegramLink;
use App\Models\User;
use App\Jobs\Telegram\SendTelegramMessageJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TelegramWebhookService
{
    public function __construct(
        protected TelegramBotService $bot,
        protected TelegramConversationService $conversation,
        protected TelegramLogService $logger,
        protected AiChatService $ai,
        protected ImageGenService $image,
        protected ChatLogService $chatLog
    ) {}

    public function handle(array $update): ?string
    {
        try {
            if (isset($update['message'])) return $this->handleMessage($update['message']);
            if (isset($update['callback_query'])) { $this->handleCallbackQuery($update['callback_query']); }
        } catch (\Exception $e) {
            Log::error('Telegram webhook error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
        return null;
    }

    // ─── MESSAGE HANDLER ───────────────────────────────────

    protected function handleMessage(array $message): ?string
    {
        $chatId = $message['chat']['id'];
        $text = trim($message['text'] ?? '');
        $from = $message['from'] ?? [];
        $telegramId = (string) ($from['id'] ?? $chatId);
        $firstName = $from['first_name'] ?? 'User';
        $username = $from['username'] ?? null;

        if (! $text) return null;

        $this->bot->sendChatAction($chatId, 'typing');
        sleep(1);

        // ── /START ────────────────────────────────────────
        if (str_starts_with($text, '/start')) {
            return $this->handleStart($chatId, $text, $telegramId, $firstName, $username);
        }

        // ── CHECK IF REGISTERED ADMIN ─────────────────────
        if (TelegramAdminChat::where('chat_id', (string) $chatId)->where('is_active', true)->exists()
            || (string) $chatId === config('telegram.admin_chat_id')) {
            return $this->handleAdminMessage($chatId, $text, $username);
        }

        // ── GET LINKED USER ───────────────────────────────
        $link = TelegramLink::where('telegram_chat_id', (string) $chatId)
            ->whereNotNull('verified_at')
            ->first();

        if (! $link) {
            $this->bot->sendMessage($chatId,
                "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "    🔗 <b>ACCOUNT REQUIRED</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "Please connect your website account first.\n\n"
                . "1️⃣ Go to our website and login\n"
                . "2️⃣ Go to <b>Settings → Telegram</b>\n"
                . "3️⃣ Click <b>\"Connect Telegram\"</b>\n"
                . "4️⃣ Copy the code and send:\n\n"
                . "<code>/start YOUR_CODE</code>\n\n"
                . "Example: <code>/start TG-938241</code>",
                [
                    [['text' => '🛍 Visit Website', 'url' => config('app.frontend_url') . '/settings/telegram']],
                ]
            );
            return 'not_linked';
        }

        $user = $link->user;
        $user->update(['last_activity_at' => now(), 'telegram_username' => $username]);
        $isAdmin = false; // Admin check already done above

        // ── CHECK USER STATE (AI / Image / Broadcast) ─────
        $state = cache()->get("tg_state_{$chatId}");
        if ($state) {
            cache()->forget("tg_state_{$chatId}");
            if ($state === 'ai_chat') { $this->handleAiChat($chatId, $user, $text); return 'ai_chat'; }
            if ($state === 'image_gen') { $this->handleImageGen($chatId, $user, $text); return 'image_gen'; }
            if ($state === 'broadcast' && $isAdmin) {
                if (strtolower($text) === '/cancel') { $this->sendAdminMenu($chatId); return 'admin_menu'; }
                $this->handleBroadcast($chatId, $text);
                return 'broadcast';
            }
            if ($state === 'add_to_cart') {
                $this->handleAddToCartSearch($chatId, $user, $text);
                return 'add_to_cart';
            }
        }

        // ── SLASH COMMANDS ────────────────────────────────
        if (str_starts_with($text, '/')) {
            return $this->handleSlashCommand($chatId, $user, $text, $isAdmin) ?? 'command';
        }

        // ── NLP INTENT MATCHING ───────────────────────────
        $analysis = $this->conversation->understand($text);
        $this->chatLog->log($user->id, 'text', 'incoming', $text, null, $analysis['intent']);

        return match ($analysis['intent']) {
            'greeting'    => $this->showMainMenu($chatId, $user, $isAdmin),
            'orders'      => $this->handleOrders($chatId, $user),
            'track'       => $this->handleTrackOrder($chatId, $user),
            'cancel'      => $this->handleCancelRequest($chatId, $user, $analysis['order_id']),
            'profile'     => $this->showProfile($chatId, $user, $isAdmin),
            'products'    => $this->showCategories($chatId),
            'help'        => $this->showHelp($chatId, $isAdmin),
            'support'     => $this->handleSupport($chatId),
            'cart'        => $this->showCart($chatId, $user),
            'thanks'      => $this->sendThanks($chatId),
            'goodbye'     => $this->sendGoodbye($chatId),
            default       => $this->handleUnknown($chatId, $user, $text),
        };
    }

    // ─── START / LINKING ──────────────────────────────────

    protected function handleStart(int $chatId, string $text, string $telegramId, string $firstName, ?string $username): string
    {
        $parts = explode(' ', $text, 2);
        $code = $parts[1] ?? null;

        // ── CHECK IF ADMIN ─────────────────────────────────
        $adminChat = TelegramAdminChat::where('chat_id', (string) $chatId)
            ->where('is_active', true)
            ->first();
        if ($adminChat) {
            $this->bot->sendMessage($chatId,
                "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "    👋 <b>WELCOME ADMIN</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "You're registered as <b>{$adminChat->role}</b>.\n"
                . "Use the admin panel to manage orders and users.",
                [
                    [['text' => '⚙️ Admin Panel', 'callback_data' => 'admin_menu']],
                    [['text' => '📦 Orders', 'callback_data' => 'admin_orders_page_1']],
                    [['text' => '📊 Analytics', 'callback_data' => 'admin_stats']],
                ]
            );
            return 'admin_welcome';
        }

        // ── ALREADY LINKED ─────────────────────────────────
        $existingLink = TelegramLink::where('telegram_chat_id', (string) $chatId)
            ->whereNotNull('verified_at')
            ->first();

        if ($existingLink) {
            $user = $existingLink->user;
            $this->showMainMenu($chatId, $user, false);
            return 'already_linked';
        }

        // ── CODE PROVIDED → LINK ───────────────────────────
        if ($code) {
            return $this->linkWithCode($chatId, $code, $telegramId, $username);
        }

        // ── NO CODE → SHOW LINKING GUIDE ──────────────────
        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    👋 <b>WELCOME TO SCENTIQUE</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "Connect your website account to get started.\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "<b>📋 HOW TO CONNECT</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "1️⃣ Go to our website and login\n"
            . "2️⃣ Go to <b>Settings → Telegram</b>\n"
            . "3️⃣ Click <b>\"Connect Telegram\"</b>\n"
            . "4️⃣ You'll get a code like: <code>TG-938241</code>\n"
            . "5️⃣ Send it here:\n\n"
            . "<code>/start TG-938241</code>\n\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "💡 <i>Already have a code? Just send it!</i>",
            [
                [['text' => '🛍 Visit Website', 'url' => config('app.frontend_url') . '/settings/telegram']],
            ]
        );
        return 'connect_guide';
    }

    // ── ADMIN MESSAGE HANDLER (no website account needed) ─

    protected function handleAdminMessage(int $chatId, string $text, ?string $username): string
    {
        $this->bot->sendChatAction($chatId, 'typing');

        // Check state (broadcast)
        $state = cache()->get("tg_state_{$chatId}");
        if ($state) {
            cache()->forget("tg_state_{$chatId}");
            if ($state === 'broadcast') {
                if (strtolower($text) === '/cancel') {
                    $this->sendAdminMenu($chatId);
                    return 'admin_menu';
                }
                $this->handleBroadcast($chatId, $text);
                return 'broadcast';
            }
        }

        // Slash commands
        if (str_starts_with($text, '/')) {
            $cmd = strtolower(explode(' ', $text)[0]);
            return match ($cmd) {
                '/start'    => $this->handleStart($chatId, $text, (string) $chatId, 'Admin', $username),
                '/menu', '/admin' => $this->sendAdminMenu($chatId),
                '/orders'   => $this->handleAdminOrderList($chatId),
                '/users'    => $this->handleAdminUsers($chatId),
                '/stats', '/analytics' => $this->handleAdminStats($chatId),
                '/broadcast'=> $this->promptBroadcast($chatId),
                '/help'     => $this->showHelp($chatId, true),
                default     => $this->sendAdminMenu($chatId),
            };
        }

        // Default → admin menu
        $this->sendAdminMenu($chatId);
        return 'admin_menu';
    }

    protected function linkWithCode(int $chatId, string $code, string $telegramId, ?string $username): string
    {
        // Look up verification code
        $link = TelegramLink::where('verification_code', $code)
            ->whereNull('verified_at')
            ->first();

        if (! $link) {
            $this->bot->sendMessage($chatId,
                "❌ <b>Invalid or expired code</b>\n\n"
                . "The code \"<code>{$code}</code>\" was not recognized.\n\n"
                . "Please generate a new code from <b>Settings → Telegram</b> on our website.\n\n"
                . "Codes expire after 15 minutes for security.",
                [
                    [['text' => '🛍 Get New Code', 'url' => config('app.frontend_url') . '/settings/telegram']],
                ]
            );
            return 'invalid_code';
        }

        // Prevent duplicate: check if this Telegram ID is already linked to another user
        $existing = TelegramLink::where('telegram_chat_id', (string) $chatId)
            ->whereNotNull('verified_at')
            ->first();
        if ($existing) {
            $this->bot->sendMessage($chatId, "❌ This Telegram account is already linked to another user.");
            return 'already_linked_other';
        }

        // Link the account
        $link->update([
            'telegram_chat_id'  => (string) $chatId,
            'telegram_username' => $username,
            'verified_at'       => now(),
            'verification_code' => null,
            'notifications_enabled' => true,
        ]);

        $user = $link->user;
        $user->update([
            'telegram_id'       => $telegramId,
            'telegram_username' => $username,
            'last_activity_at'  => now(),
        ]);

        $orderCount = Order::where('user_id', $user->id)->count();

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ✅ <b>ACCOUNT LINKED SUCCESSFULLY!</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👋 <b>Welcome, {$user->name}!</b>\n\n"
            . "Your website account is now connected to Telegram.\n"
            . "You'll receive order updates here automatically.\n\n"
            . ($orderCount > 0 ? "📦 You have <b>{$orderCount}</b> order(s) on your account.\n\n" : "")
            . "Use the menu below to get started 👇"
        );

        $isAdmin = $user->isBotAdmin() || TelegramAdminChat::where('chat_id', (string) $chatId)->where('is_active', true)->exists();
        $this->showMainMenu($chatId, $user, $isAdmin);

        return 'linked';
    }

    // ─── MAIN MENU ─────────────────────────────────────────

    protected function showMainMenu(int $chatId, User $user, bool $isAdmin = false): string
    {
        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    👋 <b>WELCOME, {$user->name}!</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "What would you like to do?",
            $this->mainMenuButtons($isAdmin)
        );
        return 'main_menu';
    }

    protected function mainMenuButtons(bool $isAdmin = false): array
    {
        // ── CUSTOMER MENU ──
        $buttons = [
            [['text' => '🛍 Browse Products', 'callback_data' => 'categories'], ['text' => '🛒 My Cart', 'callback_data' => 'cart']],
            [['text' => '📦 My Orders', 'callback_data' => 'orders'], ['text' => '🚚 Track Order', 'callback_data' => 'track_latest']],
            [['text' => '👤 My Profile', 'callback_data' => 'profile'], ['text' => '❌ Cancel Order', 'callback_data' => 'cancel_confirm']],
            [['text' => '💬 AI Assistant', 'callback_data' => 'menu_ai_chat'], ['text' => '🖼 Generate Image', 'callback_data' => 'menu_image']],
            [['text' => '❓ Help', 'callback_data' => 'help'], ['text' => '📞 Support', 'callback_data' => 'support']],
        ];

        // ── ADMIN MENU (extra permissions) ──
        if ($isAdmin) {
            $buttons[] = [['text' => '⚙️ Admin Panel', 'callback_data' => 'admin_menu']];
        }

        return $buttons;
    }

    // ─── SLASH COMMANDS ───────────────────────────────────

    protected function handleSlashCommand(int $chatId, User $user, string $text, bool $isAdmin): ?string
    {
        $cmd = strtolower(explode(' ', $text)[0]);
        return match ($cmd) {
            '/start'    => null,
            '/menu'     => $this->showMainMenu($chatId, $user, $isAdmin),
            '/chat', '/ai' => $this->promptAiChat($chatId),
            '/image'    => $this->promptImageGen($chatId),
            '/orders'   => $this->handleOrders($chatId, $user),
            '/track'    => $this->handleTrackOrder($chatId, $user),
            '/cancel'   => $this->handleCancelRequest($chatId, $user, null),
            '/profile'  => $this->showProfile($chatId, $user, $isAdmin),
            '/cart'     => $this->showCart($chatId, $user),
            '/products' => $this->showCategories($chatId),
            '/help'     => $this->showHelp($chatId, $isAdmin),
            '/support'  => $this->handleSupport($chatId),
            '/admin'    => $isAdmin ? $this->sendAdminMenu($chatId) : $this->showMainMenu($chatId, $user, $isAdmin),
            '/users'    => $isAdmin ? $this->handleAdminUsers($chatId) : null,
            '/broadcast'=> $isAdmin ? $this->promptBroadcast($chatId) : null,
            '/stats'    => $isAdmin ? $this->handleAdminStats($chatId) : null,
            default     => null,
        };
    }

    // ─── PRODUCT BROWSING ────────────────────────────────

    protected function showCategories(int $chatId): string
    {
        $categories = Category::all();

        $buttons = $categories->map(fn($c) => [
            ['text' => $c->name, 'callback_data' => "category_{$c->id}"]
        ])->values()->toArray();

        $buttons[] = [['text' => '🔍 Search Products', 'callback_data' => 'search_product']];
        $buttons[] = [['text' => '🔙 Main Menu', 'callback_data' => 'menu_main']];

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    🛍 <b>BROWSE PRODUCTS</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "Select a category:",
            $buttons
        );
        return 'categories';
    }

    protected function showProductsByCategory(int $chatId, int $categoryId, int $page = 1): void
    {
        $products = Product::where('category_id', $categoryId)
            ->paginate(5, ['*'], 'page', $page);

        $category = Category::find($categoryId);

        if ($products->isEmpty()) {
            $this->bot->sendMessage($chatId, "📭 No products in this category.", [
                [['text' => '🔙 Categories', 'callback_data' => 'categories']],
            ]);
            return;
        }

        foreach ($products as $product) {
            $price = $product->sale_price
                ? "<s>\${$product->price}</s> <b>\${$product->sale_price}</b>"
                : "<b>\${$product->price}</b>";

            $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "    <b>{$product->name}</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "💰 Price: {$price}\n"
                . "📦 Stock: {$product->stock}\n"
                . ($product->brand ? "🏷️ Brand: {$product->brand}\n" : "")
                . "\n{$product->description}\n";

            $buttons = [
                [
                    ['text' => '🛒 Add to Cart', 'callback_data' => "add_cart_{$product->id}"],
                ],
            ];
            $this->bot->sendMessage($chatId, $text, $buttons);
        }

        // Navigation
        $nav = [];
        if ($page > 1) $nav[] = ['text' => '◀️ Prev', 'callback_data' => "category_{$categoryId}_page_" . ($page - 1)];
        if ($products->hasMorePages()) $nav[] = ['text' => 'Next ▶️', 'callback_data' => "category_{$categoryId}_page_" . ($page + 1)];
        $nav[] = ['text' => '🔙 Categories', 'callback_data' => 'categories'];
        $this->bot->sendMessage($chatId, "Page {$page} of {$products->lastPage()}", [$nav]);
    }

    // ─── CART ─────────────────────────────────────────────

    protected function addToCart(int $chatId, User $user, int $productId): void
    {
        $product = Product::find($productId);
        if (! $product || $product->stock < 1) {
            $this->bot->sendMessage($chatId, "❌ This product is out of stock.");
            return;
        }

        $cartItem = Cart::firstOrCreate(
            ['user_id' => $user->id, 'product_id' => $productId],
            ['quantity' => 0]
        );
        $cartItem->increment('quantity');

        $this->bot->sendMessage($chatId,
            "✅ <b>{$product->name}</b> added to cart!\n\n"
            . "Cart quantity: {$cartItem->quantity}",
            [
                [
                    ['text' => '🛒 View Cart', 'callback_data' => 'cart'],
                    ['text' => '🛍 Continue Shopping', 'callback_data' => 'categories'],
                ],
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
            ]
        );
    }

    protected function showCart(int $chatId, User $user): string
    {
        $items = Cart::where('user_id', $user->id)->with('product')->get();

        if ($items->isEmpty()) {
            $this->bot->sendMessage($chatId, "🛒 <b>Your cart is empty</b>\n\nBrowse products and add items to get started.", [
                [['text' => '🛍 Browse Products', 'callback_data' => 'categories']],
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
            ]);
            return 'cart_empty';
        }

        $total = 0;
        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n    🛒 <b>YOUR CART</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $buttons = [];

        foreach ($items as $item) {
            $subtotal = $item->product->price * $item->quantity;
            $total += $subtotal;
            $text .= "• <b>{$item->product->name}</b> ×{$item->quantity} — \${$subtotal}\n";
            $buttons[] = [
                ['text' => "❌ Remove {$item->product->name}", 'callback_data' => "cart_remove_{$item->id}"],
            ];
        }

        $text .= "\n━━━━━━━━━━━━━━━━━━━━━━━━\n<b>Total: \${$total}</b>\n";

        $buttons[] = [
            ['text' => '🛍 Add More', 'callback_data' => 'categories'],
        ];

        // Check if user has a shipping address from previous orders
        $latestOrder = Order::where('user_id', $user->id)->latest()->first();
        if ($latestOrder && $latestOrder->shipping_address) {
            $buttons[] = [
                ['text' => '✅ Place Order', 'callback_data' => 'checkout_confirm'],
            ];
        } else {
            $buttons[] = [
                ['text' => '✅ Place Order', 'callback_data' => 'checkout_address'],
            ];
        }

        $buttons[] = [['text' => '🔙 Menu', 'callback_data' => 'menu_main']];
        $this->bot->sendMessage($chatId, $text, $buttons);

        return 'cart';
    }

    protected function checkoutStart(int $chatId, User $user): void
    {
        $items = Cart::where('user_id', $user->id)->with('product')->get();
        if ($items->isEmpty()) {
            $this->bot->sendMessage($chatId, "🛒 Your cart is empty.");
            return;
        }

        $total = $items->sum(fn($i) => $i->product->price * $i->quantity);

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ✅ <b>CONFIRM ORDER</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "📦 Items: {$items->count()}\n"
            . "💰 Total: <b>\${$total}</b>\n\n"
            . "💳 Select payment method:",
            [
                [['text' => '💵 Cash on Delivery', 'callback_data' => 'checkout_cod']],
                [['text' => '💳 Credit Card', 'callback_data' => 'checkout_cc']],
                [['text' => '🅿️ PayPal', 'callback_data' => 'checkout_pp']],
                [['text' => '🔙 Cart', 'callback_data' => 'cart']],
            ]
        );
    }

    protected function placeOrder(int $chatId, User $user, string $paymentMethod): void
    {
        $cartItems = Cart::where('user_id', $user->id)->with('product')->get();
        if ($cartItems->isEmpty()) {
            $this->bot->sendMessage($chatId, "❌ Your cart is empty.");
            return;
        }

        // Check stock
        foreach ($cartItems as $item) {
            if (! $item->product || $item->product->stock < $item->quantity) {
                $this->bot->sendMessage($chatId, "❌ <b>{$item->product->name}</b> only has {$item->product->stock} in stock.");
                return;
            }
        }

        try {
            $order = DB::transaction(function () use ($user, $cartItems, $paymentMethod) {
                $total = $cartItems->sum(fn($i) => (float) $i->product->price * $i->quantity);
                $shipping = $user->orders()->latest()->first()?->shipping_address ?? 'Address not set — please update on website';

                $order = Order::create([
                    'user_id'          => $user->id,
                    'total'            => $total,
                    'status'           => 'pending',
                    'shipping_address' => $shipping,
                    'payment_method'   => $paymentMethod,
                ]);

                foreach ($cartItems as $item) {
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'price'      => $item->product->price,
                    ]);
                    $item->product->decrement('stock', $item->quantity);
                }

                Cart::where('user_id', $user->id)->delete();
                return $order;
            });

            // Dispatch event
            \App\Events\OrderPlaced::dispatch($order);

            $this->bot->sendMessage($chatId,
                "━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "    ✅ <b>ORDER PLACED!</b>\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "📦 Order #{$order->id}\n"
                . "💰 Total: \${$order->total}\n"
                . "💳 Payment: {$paymentMethod}\n\n"
                . "We'll notify you when your order status changes.\n"
                . "Thank you for shopping with Scentique! 🎉",
                [
                    [['text' => '📦 Track Order', 'callback_data' => 'track_latest']],
                    [['text' => '🛍 Shop More', 'callback_data' => 'categories']],
                    [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
                ]
            );

        } catch (\Exception $e) {
            Log::error('Order placement failed', ['error' => $e->getMessage()]);
            $this->bot->sendMessage($chatId, "❌ Sorry, we couldn't process your order. Please try again.");
        }
    }

    // ─── ORDERS ───────────────────────────────────────────

    protected function handleOrders(int $chatId, $user): string
    {
        $orders = Order::where('user_id', $user->id)->latest()->take(5)->get();
        if ($orders->isEmpty()) {
            $this->bot->sendMessage($chatId, "📭 <b>No Orders Yet</b>\n\nStart shopping!", [
                [['text' => '🛍 Browse Products', 'callback_data' => 'categories']],
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
            ]);
            return 'orders_empty';
        }

        foreach ($orders as $order) {
            $items = $order->items->map(fn($i) => "• {$i->product?->name} ×{$i->quantity} — \${$i->price}")->implode("\n");
            $emoji = match ($order->status) { 'pending' => '⏳', 'processing' => '🔄', 'shipped' => '🚚', 'out_for_delivery' => '📍', 'delivered' => '✅', 'cancelled' => '❌', default => '📦' };
            $this->bot->sendMessage($chatId,
                "━━━━━━━━━━━━━━━━━━━━━━━━\n    {$emoji} <b>Order #{$order->id}</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "📊 <b>Status:</b> {$order->status}\n💰 <b>Total:</b> \${$order->total}\n💳 <b>Payment:</b> {$order->payment_method}\n📅 {$order->created_at->format('M d, Y')}\n\n<b>Items:</b>\n{$items}",
                [
                    [['text' => $order->status === 'pending' ? '❌ Cancel' : '🔙 Menu', 'callback_data' => $order->status === 'pending' ? "cancel_{$order->id}" : 'menu_main']],
                ]
            );
        }
        return 'orders';
    }

    protected function handleTrackOrder(int $chatId, $user): string
    {
        $order = Order::where('user_id', $user->id)
            ->whereIn('status', ['processing', 'shipped', 'out_for_delivery'])
            ->latest()->first();

        if (! $order) {
            $this->bot->sendMessage($chatId, "📭 No orders in transit.", [
                [['text' => '📦 My Orders', 'callback_data' => 'orders']],
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
            ]);
            return 'no_track';
        }

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n    🚚 <b>TRACKING — Order #{$order->id}</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "📊 <b>Status:</b> {$order->status}\n💰 \${$order->total}\n📅 {$order->created_at->format('M d, Y')}\n📬 {$order->shipping_address}",
            [ [['text' => '🔙 Menu', 'callback_data' => 'menu_main']] ]
        );
        return 'track';
    }

    protected function handleCancelRequest(int $chatId, $user, ?int $orderId): string
    {
        $q = Order::where('user_id', $user->id)->where('status', 'pending');
        if ($orderId) $q->where('id', $orderId);
        $order = $q->latest()->first();

        if (! $order) {
            $this->bot->sendMessage($chatId, "📭 No pending orders to cancel.", [
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']]
            ]);
            return 'no_cancel';
        }

        $this->bot->sendMessage($chatId,
            "⚠️ <b>Cancel Order #{$order->id}?</b>\n\n\${$order->total} — {$order->created_at->format('M d, Y')}\n\nAre you sure?",
            [
                [['text' => '✅ Yes, Cancel', 'callback_data' => "cancel_{$order->id}"]],
                [['text' => '🔙 No, Keep', 'callback_data' => 'orders']],
            ]
        );
        return 'cancel_confirm';
    }

    // ─── PROFILE ──────────────────────────────────────────

    protected function showProfile(int $chatId, User $user, bool $isAdmin): string
    {
        $orderCount = Order::where('user_id', $user->id)->count();
        $totalSpent = Order::where('user_id', $user->id)->sum('total');
        $link = TelegramLink::where('user_id', $user->id)->first();

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n    👤 <b>MY PROFILE</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🆔 <b>ID:</b> {$user->id}\n👤 <b>Name:</b> {$user->name}\n📧 <b>Email:</b> {$user->email}\n"
            . ($user->telegram_username ? "📱 <b>Telegram:</b> @{$user->telegram_username}\n" : "")
            . "📦 <b>Orders:</b> {$orderCount}\n💰 <b>Total Spent:</b> \${$totalSpent}\n"
            . "🔔 <b>Notifications:</b> " . ($link?->notifications_enabled ? '✅ On' : '❌ Off') . "\n"
            . ($isAdmin ? "🛡️ <b>Role:</b> Admin\n" : "")
            . "📅 <b>Joined:</b> " . $user->created_at->format('M d, Y'),
            [
                [['text' => '📦 My Orders', 'callback_data' => 'orders']],
                [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
            ]
        );
        return 'profile';
    }

    // ─── AI CHAT ─────────────────────────────────────────

    protected function promptAiChat(int $chatId): string
    {
        cache()->put("tg_state_{$chatId}", 'ai_chat', 300);
        $this->bot->sendMessage($chatId,
            "🧠 <b>AI Assistant</b>\n\nAsk me anything about products, orders, or just chat!\n\n<i>Send your question:</i>",
            [ [['text' => '🔙 Menu', 'callback_data' => 'menu_main']] ]
        );
        return 'ai_chat_prompt';
    }

    protected function handleAiChat(int $chatId, User $user, string $text): void
    {
        $this->bot->sendChatAction($chatId, 'typing');
        $history = $this->chatLog->getHistory($user->id, 10);
        $result = $this->ai->ask($text, $history);
        $this->chatLog->log($user->id, 'ai_chat', 'incoming', $text, $result['text'], null, null, $result['tokens']);
        $this->bot->sendMessage($chatId, $result['text'], [
            [['text' => '💬 Ask Again', 'callback_data' => 'menu_ai_chat']],
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
        ]);
    }

    // ─── IMAGE GENERATION ────────────────────────────────

    protected function promptImageGen(int $chatId): string
    {
        cache()->put("tg_state_{$chatId}", 'image_gen', 300);
        $this->bot->sendMessage($chatId,
            "🖼 <b>Generate Image</b>\n\nDescribe what you'd like to see.\n<i>Example: \"a luxury perfume bottle in gold\"</i>",
            [ [['text' => '🔙 Menu', 'callback_data' => 'menu_main']] ]
        );
        return 'image_prompt';
    }

    protected function handleImageGen(int $chatId, User $user, string $text): void
    {
        $this->bot->sendChatAction($chatId, 'typing');
        $this->bot->sendMessage($chatId, "🎨 Generating... Please wait.");
        $result = $this->image->generate($text);
        if ($result['file']) {
            $fullPath = public_path($result['file']);
            if (file_exists($fullPath)) $this->bot->sendPhoto($chatId, $fullPath, $result['text']);
            else $this->bot->sendMessage($chatId, $result['text']);
        } else {
            $this->bot->sendMessage($chatId, $result['text']);
        }
        $this->chatLog->log($user->id, 'image_gen', 'incoming', $text, $result['text'] ?? '');
        $this->bot->sendMessage($chatId, "✅ Done!", [
            [['text' => '🖼 Generate Another', 'callback_data' => 'menu_image']],
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
        ]);
    }

    // ─── HELP / SUPPORT / THANKS / GOODBYE ───────────────

    protected function showHelp(int $chatId, bool $isAdmin): string
    {
        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n    ❓ <b>HELP & COMMANDS</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "🗣 <b>Try saying:</b>\n• \"Hi\" — Main menu\n• \"My Orders\" — View orders\n• \"Track\" — Track shipment\n• \"Cancel\" — Cancel order\n"
            . "• \"Profile\" — My info\n• \"Cart\" — View cart\n\n"
            . "⌨️ <b>Commands:</b>\n/menu — Main menu\n/chat — AI Assistant\n/image — Generate image\n"
            . "/orders — My orders\n/cart — My cart\n/track — Track order\n"
            . "/profile — My profile\n/help — This guide\n/support — Contact us\n";
        if ($isAdmin) $text .= "\n🛡 <b>Admin:</b>\n/admin — Admin panel\n/users — User list\n/stats — Analytics\n/broadcast — Send message";

        $this->bot->sendMessage($chatId, $text, [
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
        ]);
        return 'help';
    }

    protected function handleSupport(int $chatId): string
    {
        $this->bot->sendMessage($chatId,
            "📞 <b>Support</b>\n\n📧 Email: support@scentique.com\n🌐 " . config('app.frontend_url') . "/contact\n\nWe respond within 24 hours.",
            [ [['text' => '🔙 Menu', 'callback_data' => 'menu_main']] ]
        );
        return 'support';
    }

    protected function sendThanks(int $chatId): string
    {
        $this->bot->sendMessage($chatId, "😊 You're welcome!", [
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']]
        ]);
        return 'thanks';
    }

    protected function sendGoodbye(int $chatId): string
    {
        $this->bot->sendMessage($chatId, "👋 Take care!", [
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']]
        ]);
        return 'goodbye';
    }

    // ─── UNKNOWN ─────────────────────────────────────────

    protected function handleUnknown(int $chatId, User $user, string $text): string
    {
        // Long text → AI chat
        if (strlen($text) > 20) {
            $this->handleAiChat($chatId, $user, $text);
            return 'ai_chat';
        }
        // Check for order number
        if (preg_match('/order\s*#?\s*(\d+)/i', $text, $m)) {
            $order = Order::where('id', (int) $m[1])->where('user_id', $user->id)->first();
            if ($order) return $this->handleOrders($chatId, $user);
        }
        $this->bot->sendMessage($chatId, "I didn't understand that.\n\nTry /menu or /help", [
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
        ]);
        return 'unknown';
    }

    // ─── CALLBACK QUERIES ────────────────────────────────

    protected function handleCallbackQuery(array $callback): void
    {
        $data = $callback['data'] ?? '';
        $callbackId = $callback['id'] ?? '';
        $chatId = $callback['message']['chat']['id'] ?? null;
        if (! $chatId) return;

        $this->bot->answerCallbackQuery($callbackId);

        // ── CHECK IF ADMIN FIRST ───────────────────────────
        $adminChat = TelegramAdminChat::where('chat_id', (string) $chatId)
            ->where('is_active', true)
            ->first();

        if ($adminChat) {
            match (true) {
                $data === 'admin_menu'         => $this->sendAdminMenu($chatId),
                $data === 'admin_users'        => $this->handleAdminUsers($chatId),
                $data === 'admin_stats'        => $this->handleAdminStats($chatId),
                $data === 'admin_broadcast'    => $this->promptBroadcast($chatId),
                $data === 'admin_toggle_ai'    => $this->toggleSetting($chatId, 'ai_enabled'),
                $data === 'admin_toggle_image' => $this->toggleSetting($chatId, 'image_enabled'),
                preg_match('/^admin_orders_page_(\d+)$/', $data, $m) ? true : false => $this->handleAdminOrderList($chatId, (int) $m[1]),
                preg_match('/^admin_accept_order_(\d+)$/', $data, $m) ? true : false => $this->adminAcceptOrder($chatId, (int) $m[1]),
                preg_match('/^admin_reject_order_(\d+)$/', $data, $m) ? true : false => $this->adminRejectOrder($chatId, (int) $m[1]),
                preg_match('/^admin_ship_order_(\d+)$/', $data, $m) ? true : false => $this->adminUpdateStatus($chatId, (int) $m[1], 'shipped'),
                preg_match('/^admin_deliver_order_(\d+)$/', $data, $m) ? true : false => $this->adminUpdateStatus($chatId, (int) $m[1], 'delivered'),
                preg_match('/^admin_restock_(\d+)_(\d+)$/', $data, $m) ? true : false => $this->adminRestock($chatId, (int) $m[1], (int) $m[2]),
                $data === 'menu_main'          => $this->sendAdminMenu($chatId),
                $data === 'help'               => $this->showHelp($chatId, true),
                default                        => $this->bot->answerCallbackQuery($callbackId, 'Admin: Unknown option'),
            };
            return;
        }

        // ── GET LINKED USER (CUSTOMER) ─────────────────────
        $link = TelegramLink::where('telegram_chat_id', (string) $chatId)->whereNotNull('verified_at')->first();
        if (! $link) {
            $this->bot->answerCallbackQuery($callbackId, 'Please connect your account first. Use /start');
            return;
        }
        $user = $link->user;
        $user->update(['last_activity_at' => now()]);

        match (true) {
            // Navigation
            $data === 'menu_main'          => $this->showMainMenu($chatId, $user, false),
            $data === 'menu_ai_chat'       => $this->promptAiChat($chatId),
            $data === 'menu_image'         => $this->promptImageGen($chatId),

            // Categories
            $data === 'categories'         => $this->showCategories($chatId),
            $data === 'search_product'     => $this->promptSearchProduct($chatId),
            preg_match('/^category_(\d+)$/', $data, $m) ? true : false => $this->showProductsByCategory($chatId, (int) $m[1]),
            preg_match('/^category_(\d+)_page_(\d+)$/', $data, $m) ? true : false => $this->showProductsByCategory($chatId, (int) $m[1], (int) $m[2]),

            // Cart
            $data === 'cart'               => $this->showCart($chatId, $user),
            preg_match('/^add_cart_(\d+)$/', $data, $m) ? true : false => $this->addToCart($chatId, $user, (int) $m[1]),
            preg_match('/^cart_remove_(\d+)$/', $data, $m) ? true : false => $this->removeFromCart($chatId, $user, (int) $m[1]),

            // Checkout
            $data === 'checkout_confirm'   => $this->checkoutStart($chatId, $user),
            $data === 'checkout_address'   => $this->promptAddress($chatId),
            $data === 'checkout_cod'       => $this->placeOrder($chatId, $user, 'cash_on_delivery'),
            $data === 'checkout_cc'        => $this->placeOrder($chatId, $user, 'credit_card'),
            $data === 'checkout_pp'        => $this->placeOrder($chatId, $user, 'paypal'),

            // Orders
            $data === 'orders'             => $this->handleOrders($chatId, $user),
            $data === 'track_latest'       => $this->handleTrackOrder($chatId, $user),
            $data === 'cancel_confirm'     => $this->handleCancelRequest($chatId, $user, null),
            preg_match('/^cancel_(\d+)$/', $data, $m) ? true : false => $this->executeCancel($chatId, $user, (int) $m[1]),

            // Profile / Help / Support
            $data === 'profile'            => $this->showProfile($chatId, $user, false),
            $data === 'help'               => $this->showHelp($chatId, false),
            $data === 'support'            => $this->handleSupport($chatId),

            default => $this->bot->answerCallbackQuery($callbackId, 'Unknown option'),
        };
    }

    // ─── SEARCH ──────────────────────────────────────────

    protected function promptSearchProduct(int $chatId): void
    {
        cache()->put("tg_state_{$chatId}", 'add_to_cart', 300);
        $this->bot->sendMessage($chatId,
            "🔍 <b>Search Products</b>\n\nType a product name or keyword to search.",
            [ [['text' => '🔙 Categories', 'callback_data' => 'categories']] ]
        );
    }

    protected function handleAddToCartSearch(int $chatId, User $user, string $text): void
    {
        $products = Product::where('name', 'like', "%{$text}%")->take(5)->get();
        if ($products->isEmpty()) {
            $this->bot->sendMessage($chatId, "📭 No products found for \"{$text}\"", [
                [['text' => '🔙 Categories', 'callback_data' => 'categories']]
            ]);
            return;
        }
        foreach ($products as $product) {
            $this->bot->sendMessage($chatId,
                "📦 <b>{$product->name}</b>\n💰 \${$product->price}\n{$product->description}",
                [ [['text' => '🛒 Add to Cart', 'callback_data' => "add_cart_{$product->id}"]] ]
            );
        }
    }

    // ─── CART HELPERS ────────────────────────────────────

    protected function removeFromCart(int $chatId, User $user, int $cartId): void
    {
        $item = Cart::where('id', $cartId)->where('user_id', $user->id)->first();
        if ($item) { $item->delete(); $this->bot->sendMessage($chatId, "✅ Removed from cart."); }
        $this->showCart($chatId, $user);
    }

    protected function promptAddress(int $chatId): void
    {
        cache()->put("tg_state_{$chatId}", 'set_address', 300);
        $this->bot->sendMessage($chatId,
            "📬 <b>Shipping Address</b>\n\nPlease enter your shipping address:\n\n"
            . "<i>Format: Street, City, Zip Code, Country</i>",
            [ [['text' => '🔙 Cart', 'callback_data' => 'cart']] ]
        );
    }

    protected function executeCancel(int $chatId, $user, int $orderId): void
    {
        $order = Order::where('id', $orderId)->where('user_id', $user->id)->where('status', 'pending')->first();
        if (! $order) {
            $this->bot->sendMessage($chatId, "❌ Cannot cancel. Order already processed.");
            return;
        }
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) { Product::where('id', $item->product_id)->increment('stock', $item->quantity); }
            $order->update(['status' => 'cancelled']);
            OrderStatusUpdated::dispatch($order, 'pending', 'cancelled');
        });
        $this->bot->sendMessage($chatId, "✅ Order #{$orderId} cancelled.", [
            [['text' => '📦 My Orders', 'callback_data' => 'orders']],
            [['text' => '🔙 Menu', 'callback_data' => 'menu_main']],
        ]);
    }

    // ─── ADMIN PANEL ─────────────────────────────────────

    protected function sendAdminMenu(int $chatId): string
    {
        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n"
            . "    ⚙️ <b>ADMIN PANEL</b>\n"
            . "━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "<b>📦 Orders Management</b>\n"
            . "└ Manage all orders, update status\n\n"
            . "<b>👥 Users</b>\n"
            . "└ View registered users & linked accounts\n\n"
            . "<b>📊 Analytics</b>\n"
            . "└ Revenue, orders, bot usage stats\n\n"
            . "<b>📢 Broadcast</b>\n"
            . "└ Send message to all linked users\n\n"
            . "<b>⚙️ Bot Settings</b>\n"
            . "└ Toggle AI Chat / Image Generation\n\n"
            . "Select an option below:",
            [
                [['text' => '📦 Orders', 'callback_data' => 'admin_orders_page_1'], ['text' => '👥 Users', 'callback_data' => 'admin_users']],
                [['text' => '📊 Analytics', 'callback_data' => 'admin_stats'], ['text' => '📢 Broadcast', 'callback_data' => 'admin_broadcast']],
                [['text' => '⚙️ Settings', 'callback_data' => 'admin_toggle_ai'], ['text' => '🔄 Refresh', 'callback_data' => 'admin_menu']],
                [['text' => '🔙 Main Menu', 'callback_data' => 'menu_main']],
            ]
        );
        return 'admin_menu';
    }

    protected function handleAdminUsers(int $chatId): string
    {
        $total = User::count();
        $today = User::whereDate('created_at', today())->count();
        $active7d = User::where('last_activity_at', '>=', now()->subDays(7))->count();
        $linked = TelegramLink::whereNotNull('verified_at')->count();
        $recent = User::latest()->take(5)->get();

        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n    👥 <b>USERS</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👤 Total: {$total}\n📅 Today: {$today}\n🟢 Active (7d): {$active7d}\n🔗 Linked: {$linked}\n\n<b>Recent:</b>\n";
        foreach ($recent as $u) $text .= "• {$u->name} ({$u->email})\n";

        $this->bot->sendMessage($chatId, $text, [
            [['text' => '🔄 Refresh', 'callback_data' => 'admin_users']],
            [['text' => '🔙 Admin', 'callback_data' => 'admin_menu']],
        ]);
        return 'admin_users';
    }

    protected function handleAdminStats(int $chatId): string
    {
        $logs = $this->chatLog->getStats();
        $totalUsers = User::count();
        $ordersToday = Order::whereDate('created_at', today())->count();
        $revenueToday = Order::whereDate('created_at', today())->where('status', 'delivered')->sum('total');
        $pendingOrders = Order::where('status', 'pending')->count();

        $this->bot->sendMessage($chatId,
            "━━━━━━━━━━━━━━━━━━━━━━━━\n    📊 <b>ANALYTICS</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
            . "👥 Users: {$totalUsers}\n📦 Orders Today: {$ordersToday}\n⏳ Pending: {$pendingOrders}\n💰 Revenue: \${$revenueToday}\n\n"
            . "🤖 <b>Bot (24h):</b>\n• Interactions: {$logs['chats_24h']}\n• AI: {$logs['ai_chats_24h']}\n• Images: {$logs['images_24h']}",
            [ [['text' => '🔄 Refresh', 'callback_data' => 'admin_stats']], [['text' => '🔙 Admin', 'callback_data' => 'admin_menu']] ]
        );
        return 'admin_stats';
    }

    protected function promptBroadcast(int $chatId): string
    {
        cache()->put("tg_state_{$chatId}", 'broadcast', 300);
        $this->bot->sendMessage($chatId,
            "📢 <b>Broadcast Message</b>\n\nType your message:\nSend /cancel to abort.",
            [ [['text' => '🔙 Cancel', 'callback_data' => 'admin_menu']] ]
        );
        return 'broadcast_prompt';
    }

    protected function handleBroadcast(int $chatId, string $text): string
    {
        $users = TelegramLink::whereNotNull('verified_at')->where('notifications_enabled', true)->get();
        $count = 0;
        foreach ($users as $link) {
            SendTelegramMessageJob::dispatch($link->telegram_chat_id, $text);
            $count++;
        }
        $this->chatLog->log(0, 'broadcast', 'outgoing', $text, null, 'broadcast', ['recipients' => $count]);
        $this->bot->sendMessage($chatId, "📢 Broadcast sent to {$count} users.", [
            [['text' => '🔙 Admin', 'callback_data' => 'admin_menu']]
        ]);
        return 'broadcast';
    }

    protected function toggleSetting(int $chatId, string $key): string
    {
        $current = BotSetting::isEnabled($key);
        BotSetting::set($key, ! $current);
        $this->bot->sendMessage($chatId, "✅ " . ($key === 'ai_enabled' ? 'AI Chat' : 'Image') . " " . (! $current ? 'enabled' : 'disabled'));
        $this->sendAdminMenu($chatId);
        return 'settings';
    }

    // ─── ADMIN ORDERS ────────────────────────────────────

    protected function handleAdminOrderList(int $chatId, int $page = 1): string
    {
        $orders = Order::with('user')->latest()->paginate(5, ['*'], 'page', $page);
        $text = "━━━━━━━━━━━━━━━━━━━━━━━━\n    📦 <b>ORDERS (Page {$orders->currentPage()}/{$orders->lastPage()})</b>\n━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $buttons = [];
        foreach ($orders as $order) {
            $emoji = match ($order->status) { 'pending' => '⏳', 'processing' => '🔄', 'shipped' => '🚚', 'delivered' => '✅', 'cancelled' => '❌', default => '📦' };
            $text .= "{$emoji} <b>#{$order->id}</b> — {$order->user->name} — \${$order->total}\n";
            $actions = match ($order->status) {
                'pending' => [['text' => '✅ Accept', 'callback_data' => "admin_accept_order_{$order->id}"], ['text' => '❌ Reject', 'callback_data' => "admin_reject_order_{$order->id}"]],
                'processing' => [['text' => '🚚 Ship', 'callback_data' => "admin_ship_order_{$order->id}"]],
                'shipped' => [['text' => '✅ Deliver', 'callback_data' => "admin_deliver_order_{$order->id}"]],
                default => []
            };
            if ($actions) $buttons[] = $actions;
        }
        $nav = [];
        if ($page > 1) $nav[] = ['text' => '◀️ Prev', 'callback_data' => 'admin_orders_page_' . ($page - 1)];
        if ($orders->hasMorePages()) $nav[] = ['text' => 'Next ▶️', 'callback_data' => 'admin_orders_page_' . ($page + 1)];
        if ($nav) $buttons[] = $nav;
        $buttons[] = [['text' => '🔙 Admin', 'callback_data' => 'admin_menu']];
        $this->bot->sendMessage($chatId, $text, $buttons);
        return 'admin_orders';
    }

    protected function adminAcceptOrder(int $chatId, int $orderId): string
    {
        DB::transaction(function () use ($orderId, $chatId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);
            if ($order->status !== 'pending') { $this->bot->sendMessage($chatId, "⚠️ Already {$order->status}"); return; }
            $order->update(['status' => 'processing']);
            OrderStatusUpdated::dispatch($order, 'pending', 'processing');
            $this->bot->sendMessage($chatId, "✅ Order #{$orderId} → Processing");
        });
        return 'order_accepted';
    }

    protected function adminRejectOrder(int $chatId, int $orderId): string
    {
        DB::transaction(function () use ($orderId, $chatId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);
            if ($order->status !== 'pending') { $this->bot->sendMessage($chatId, "⚠️ Already {$order->status}"); return; }
            foreach ($order->items as $item) { Product::where('id', $item->product_id)->increment('stock', $item->quantity); }
            $order->update(['status' => 'cancelled']);
            OrderStatusUpdated::dispatch($order, 'pending', 'cancelled');
            $this->bot->sendMessage($chatId, "❌ Order #{$orderId} Rejected");
        });
        return 'order_rejected';
    }

    protected function adminUpdateStatus(int $chatId, int $orderId, string $status): string
    {
        DB::transaction(function () use ($orderId, $status, $chatId) {
            $order = Order::lockForUpdate()->findOrFail($orderId);
            $allowed = match ($order->status) { 'processing' => ['shipped'], 'shipped' => ['delivered'], default => [] };
            if (! in_array($status, $allowed)) { $this->bot->sendMessage($chatId, "⚠️ Cannot change from {$order->status}"); return; }
            $order->update(['status' => $status]);
            OrderStatusUpdated::dispatch($order, $order->getOriginal('status'), $status);
            $this->bot->sendMessage($chatId, "✅ Order #{$orderId} → {$status}");
        });
        return 'status_updated';
    }

    protected function adminRestock(int $chatId, int $productId, int $qty): string
    {
        $product = Product::find($productId);
        if (! $product) { $this->bot->sendMessage($chatId, "❌ Product not found"); return 'restock_failed'; }
        $product->increment('stock', $qty);
        $this->bot->sendMessage($chatId, "✅ {$product->name} restocked +{$qty} (now {$product->stock})");
        return 'restocked';
    }
}
