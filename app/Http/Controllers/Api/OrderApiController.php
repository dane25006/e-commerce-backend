<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Cart, Order, OrderItem, Product};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Storage};
use OpenApi\Attributes as OA;

class OrderApiController extends Controller
{
    #[OA\Post(
        path: '/api/checkout',
        summary: 'Checkout and create order',
        description: 'Convert the authenticated user\'s cart into an order. Validates stock, creates order items, decrements stock, and clears the cart.',
        tags: ['Orders'],
        operationId: 'orderCheckout',
        security: [
            ['sanctum' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['shipping_address', 'payment_method'],
                properties: [
                    new OA\Property(property: 'shipping_address', type: 'string', description: 'Shipping address'),
                    new OA\Property(property: 'payment_method', type: 'string', description: 'Payment method', enum: ['cash_on_delivery', 'credit_card', 'paypal']),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order placed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/checkout  [auth:sanctum] ───────────────────────────────
    // Converts the logged-in user's cart into a real order.
    // Expects: shipping_address (string), payment_method (string)
    //
    // Flow:
    //  1. Validate shipping fields
    //  2. Check cart is not empty
    //  3. Check every item has enough stock
    //  4. DB transaction: create order → create order_items → decrement stock → clear cart
    //  5. Return the new order
    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => ['required', 'string', 'max:500'],
            'payment_method'   => ['required', 'string', 'in:cash_on_delivery,credit_card,paypal'],
        ]);

        $user = $request->user();

        // Load cart with products
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        // Cart must not be empty
        if ($cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Your cart is empty. Add some items before checking out.',
            ], 422);
        }

        // Pre-flight stock check — collect all problems before touching the DB
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
            return response()->json([
                'message' => 'Some items in your cart have stock issues. Please review and update your cart.',
                'errors'  => $stockErrors,
            ], 422);
        }

        // All good — wrap everything in a transaction
        // If any step fails, the whole thing rolls back (no partial orders)
        try {
            $order = DB::transaction(function () use ($user, $cartItems, $validated) {

                // Step 1 — calculate grand total
                $total = $cartItems->sum(
                    fn($c) => (float) $c->product->price * $c->quantity
                );

                // Step 2 — create the order record
                $order = Order::create([
                    'user_id'          => $user->id,
                    'total'            => $total,
                    'status'           => 'pending',
                    'shipping_address' => $validated['shipping_address'],
                    'payment_method'   => $validated['payment_method'],
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
                'message' => 'We couldn\'t process your checkout. Please try again.',
            ], 500);
        }

        // Load the full order to return in the response
        $order->load('items.product');

        return response()->json([
            'message' => 'Thank you! Your order has been placed.',
            'order'   => $this->formatOrder($order),
        ], 201);
    }

    #[OA\Get(
        path: '/api/orders',
        summary: 'List user orders',
        description: 'Retrieve paginated order history for the authenticated user.',
        tags: ['Orders'],
        operationId: 'orderIndex',
        security: [
            ['sanctum' => []],
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'orders', type: 'array', items: new OA\Items(ref: '#/components/schemas/Order')),
                        new OA\Property(property: 'meta', properties: [
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    // ── GET /api/orders  [auth:sanctum] ──────────────────────────────────
    // Returns only the authenticated user's own order history
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->withCount('items')
            ->latest()
            ->paginate(10);

        $orders->through(fn($o) => [
            'id'           => $o->id,
            'status'       => $o->status,
            'total'        => (float) $o->total,
            'items_count'  => $o->items_count,
            'created_at'   => $o->created_at->toDateTimeString(),
        ]);

        return response()->json([
            'orders' => $orders->items(),
            'meta'   => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/orders/{order}',
        summary: 'Get order details',
        description: 'Retrieve full details of a specific order including all items.',
        tags: ['Orders'],
        operationId: 'orderShow',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'order',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Order ID'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'order', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    // ── GET /api/orders/{order}  [auth:sanctum] ──────────────────────────
    // Returns full detail of one order including all items
    public function show(Request $request, Order $order)
    {
        // Security — users can only see their own orders
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'We couldn\'t find that order.'], 404);
        }

        $order->load('items.product');

        return response()->json([
            'order' => $this->formatOrder($order),
        ]);
    }

    #[OA\Put(
        path: '/api/orders/{order}/cancel',
        summary: 'Cancel an order',
        description: 'Cancel a pending order and restore stock for each item.',
        tags: ['Orders'],
        operationId: 'orderCancel',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'order',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Order ID'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order cancelled',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order', ref: '#/components/schemas/Order'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── PUT /api/orders/{order}/cancel  [auth:sanctum] ───────────────────
    // Customer can cancel a PENDING order only.
    // Cancelling restores stock for each item.
    public function cancel(Request $request, Order $order)
    {
        // Ownership check
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'We couldn\'t find that order.'], 404);
        }

        // Can only cancel pending orders
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled. '
                           . 'This order is currently "' . $order->status . '".',
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
                'message' => 'We couldn\'t cancel your order. Please try again.',
            ], 500);
        }

        return response()->json([
            'message' => 'Order #' . $order->id . ' has been cancelled and stock has been restored.',
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
                                    ? url('api/storage/' . $item->product->image)
                                    : null,
                ],
            ])->values(),
        ];
    }
}