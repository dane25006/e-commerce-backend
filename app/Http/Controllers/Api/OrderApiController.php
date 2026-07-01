<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\OrderPlaced;
use App\Events\OrderStatusUpdated;
use App\Http\Requests\CheckoutRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\Order;
use App\Services\Order\OrderService;
use App\Services\Telegram\TelegramNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OrderService $orderService,
        protected TelegramNotificationService $telegram
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        if ($cartItems->isEmpty()) {
            return $this->unprocessable('Your cart is empty. Add some items before checking out.');
        }

        $stockErrors = [];
        foreach ($cartItems as $item) {
            if (! $item->product) {
                $stockErrors[] = 'An item in your cart is no longer available. Please remove it to continue.';
                continue;
            }
            if ($item->product->stock < $item->quantity) {
                $stockErrors[] = '"' . $item->product->name . '" only has '
                    . $item->product->stock . ' unit(s) in stock, but you have '
                    . $item->quantity . ' in your cart.';
            }
        }

        if (! empty($stockErrors)) {
            return $this->unprocessable(
                'Some items in your cart have stock issues. Please review and update your cart.',
                $stockErrors
            );
        }

        try {
            $order = $this->orderService->createOrder(
                $user->id,
                $request->shipping_address,
                $request->payment_method
            );
        } catch (\Exception $e) {
            return $this->error("We couldn't process your checkout. Please try again.", 500);
        }

        OrderPlaced::dispatch($order);

        return $this->created([
            'order' => $this->orderService->formatOrderDetail($order),
        ], 'Thank you! Your order has been placed.');
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        $orders = Order::where('user_id', $user->id)
            ->withCount('items')
            ->latest()
            ->paginate(10);

        $orders->through(fn($o) => $this->orderService->formatOrderSummary($o));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user('sanctum')->id) {
            return $this->notFound('Order not found.');
        }

        $order->load('items.product');

        return $this->success([
            'order' => $this->orderService->formatOrderDetail($order),
        ]);
    }

    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user('sanctum')->id) {
            return $this->notFound('Order not found.');
        }

        if ($order->status !== 'pending') {
            return $this->unprocessable('Only pending orders can be cancelled.');
        }

        $oldStatus = $order->status;

        $this->orderService->cancelOrder($order);

        $order->refresh();
        OrderStatusUpdated::dispatch($order, $oldStatus, 'cancelled');

        return $this->success(null, 'Your order has been cancelled.');
    }
}
