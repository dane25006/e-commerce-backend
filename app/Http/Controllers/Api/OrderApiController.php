<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Cart, Order, OrderItem, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};

class OrderApiController extends Controller
{
    // ── POST /api/checkout  [auth:sanctum] ───────────────────────────────
    // Converts the logged-in user's cart into a real order.
    // No request body needed — reads from cart automatically.
    //
    // Flow:
    //  1. Check cart is not empty
    //  2. Check every item has enough stock
    //  3. DB transaction: create order → create order_items → decrement stock → clear cart
    //  4. Return the new order
    public function checkout(Request $request)
    {
        $user = $request->user();

        // Load cart with products
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        // Cart must not be empty
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty. Add products before checking out.',
            ], 422);
        }

        // Pre-flight stock check — collect all problems before touching the DB
        $stockErrors = [];
        foreach ($cartItems as $item) {
            if (! $item->product) {
                $stockErrors[] = 'A product in your cart no longer exists. Please remove it.';
                continue;
            }
            if ($item->product->stock < $item->quantity) {
                $stockErrors[] = '"' . $item->product->name . '" only has '
                    . $item->product->stock . ' unit(s) in stock '
                    . '(you have ' . $item->quantity . ' in your cart).';
            }
        }

        if (! empty($stockErrors)) {
            return response()->json([
                'message' => 'Some items in your cart have stock issues. Please update your cart.',
                'errors'  => $stockErrors,
            ], 422);
        }

        // All good — wrap everything in a transaction
        // If any step fails, the whole thing rolls back (no partial orders)
        try {
            $order = DB::transaction(function () use ($user, $cartItems) {

                // Step 1 — calculate grand total
                $total = $cartItems->sum(
                    fn($c) => (float) $c->product->price * $c->quantity
                );

                // Step 2 — create the order record
                $order = Order::create([
                    'user_id'      => $user->id,
                    'total'        => $total,
                    'status'       => 'pending',
                ]);

                // Step 3 — create one OrderItem per cart line
                foreach ($cartItems as $item) {
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'price'      => $item->product->price, // price snapshot
                    ]);

                    // Step 4 — reduce stock
                    $item->product->decrement('stock', $item->quantity);
                }

                // Step 5 — clear the cart
                Cart::where('user_id', $user->id)->delete();

                return $order;
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Checkout failed. Please try again.',
            ], 500);
        }

        // Load the full order to return in the response
        $order->load('items.product');

        return response()->json([
            'message' => 'Order placed successfully!',
            'order'   => $this->formatOrder($order),
        ], 201);
    }

    // ── GET /api/orders  [auth:sanctum] ──────────────────────────────────
    // Returns only the authenticated user's own order history
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(10);

        return response()->json([
            'orders' => $orders->through(fn($o) => [
                'id'           => $o->id,
                'status'       => $o->status,
                'total' => (float) $o->total,
                'items_count'  => $o->items_count,
                'created_at'   => $o->created_at->toDateTimeString(),
            ]),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    // ── GET /api/orders/{order}  [auth:sanctum] ──────────────────────────
    // Returns full detail of one order including all items
    public function show(Request $request, Order $order)
    {
        // Security — users can only see their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $order->load('items.product');

        return response()->json([
            'order' => $this->formatOrder($order),
        ]);
    }

    // ── PUT /api/orders/{order}/cancel  [auth:sanctum] ───────────────────
    // Customer can cancel a PENDING order only.
    // Cancelling restores stock for each item.
    public function cancel(Request $request, Order $order)
    {
        // Ownership check
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        // Can only cancel pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled. '
                           . 'This order is "' . $order->status . '".',
            ], 422);
        }

        try {
            DB::transaction(function () use ($order) {

                // Restore stock for each item
                foreach ($order->items()->with('product')->get() as $item) {
                    if ($item->product) {
                        $item->product->increment('stock', $item->quantity);
                    }
                }

                // Mark order as cancelled
                $order->update(['status' => 'cancelled']);
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Cancellation failed. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Order #' . $order->id . ' has been cancelled. Stock has been restored.',
            'order'   => $this->formatOrder($order->fresh()->load('items.product')),
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatOrder(Order $order): array
    {
        return [
            'id'           => $order->id,
            'status'       => $order->status,
            'total' => (float) $order->total,
            'created_at'   => $order->created_at->toDateTimeString(),
            'updated_at'   => $order->updated_at->toDateTimeString(),
            'items'        => $order->items->map(fn($item) => [
                'id'       => $item->id,
                'quantity' => $item->quantity,
                'price'    => (float) $item->price,
                'subtotal' => round((float) $item->price * $item->quantity, 2),
                'product'  => [
                    'id'        => $item->product?->id,
                    'name'      => $item->product?->name ?? 'Product deleted',
                    'image_url' => $item->product?->image
                                    ? Storage::url($item->product->image)
                                    : null,
                ],
            ])->values(),
        ];
    }
}