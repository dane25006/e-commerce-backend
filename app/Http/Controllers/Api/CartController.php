<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    #[OA\Get(
        path: '/api/cart',
        summary: 'Get cart items',
        description: 'Retrieve all items in the cart for the authenticated user or guest.',
        tags: ['Cart'],
        operationId: 'cartIndex',
        parameters: [
            new OA\Parameter(
                name: 'guest_token',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Guest token for unauthenticated users'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'cart', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItem')),
                        new OA\Property(property: 'item_count', type: 'integer'),
                        new OA\Property(property: 'line_count', type: 'integer'),
                        new OA\Property(property: 'total', type: 'number', format: 'float'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    // ── GET /api/cart ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Cart::with('product.category');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('guest_token')) {
            $query->where('guest_token', $request->guest_token);
        } else {
            return response()->json(['cart' => [], 'item_count' => 0, 'line_count' => 0, 'total' => 0]);
        }

        $items = $query->get()->map(fn($c) => $this->formatItem($c));
        $subtotal  = $items->sum(fn($i) => $i['subtotal']);
        $itemCount = $items->sum(fn($i) => $i['quantity']);

        return response()->json([
            'cart'       => $items,
            'item_count' => $itemCount,
            'line_count' => $items->count(),
            'total'      => round($subtotal, 2),
        ]);
    }

    #[OA\Post(
        path: '/api/cart',
        summary: 'Add item to cart',
        description: 'Add a product to the cart. If the product already exists, the quantity is incremented.',
        tags: ['Cart'],
        operationId: 'cartStore',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', description: 'Product ID'),
                    new OA\Property(property: 'quantity', type: 'integer', description: 'Quantity to add'),
                    new OA\Property(property: 'guest_token', type: 'string', description: 'Guest token for unauthenticated users'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Item added to cart',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'item', ref: '#/components/schemas/CartItem'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/cart ────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $productId = $request->product_id;
        $quantity  = (int) $request->quantity;
        $product   = Product::findOrFail($productId);

        if ($product->stock === 0) {
            return response()->json(['message' => $product->name . ' is currently out of stock.'], 422);
        }

        $ownerId = $request->user() ? $request->user()->id : null;
        $token   = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
        }

        $query = Cart::where('product_id', $productId);
        if ($ownerId) {
            $query->where('user_id', $ownerId);
        } else {
            $query->where('guest_token', $token);
        }
        $existing = $query->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            if ($product->stock < $newQty) {
                return response()->json([
                    'message' => 'We only have '
                        . ($product->stock - $existing->quantity) . ' more of this item in stock.',
                ], 422);
            }
            $existing->update(['quantity' => $newQty]);
            $cart = $existing->load('product.category');
        } else {
            if ($product->stock < $quantity) {
                return response()->json([
                    'message' => 'Only ' . $product->stock . ' units available. Please adjust the quantity.',
                ], 422);
            }
            $data = ['product_id' => $productId, 'quantity' => $quantity];
            if ($ownerId) {
                $data['user_id'] = $ownerId;
            } else {
                $data['guest_token'] = $token;
            }
            $cart = Cart::create($data);
            $cart->load('product.category');
        }

        return response()->json(['message' => 'Added to your cart', 'item' => $this->formatItem($cart)], 201);
    }

    #[OA\Put(
        path: '/api/cart/{cart}',
        summary: 'Update cart item quantity',
        description: 'Update the quantity of a specific cart item.',
        tags: ['Cart'],
        operationId: 'cartUpdate',
        parameters: [
            new OA\Parameter(
                name: 'cart',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Cart item ID'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', description: 'New quantity'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart item updated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'item', ref: '#/components/schemas/CartItem'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── PUT /api/cart/{cart} ─────────────────────────────────────────────
    public function update(Request $request, Cart $cart)
    {
        if (! $this->owns($request, $cart)) {
            return response()->json(['message' => 'We couldn\'t find what you\'re looking for.'], 404);
        }

        $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $newQty  = (int) $request->quantity;
        $product = $cart->product;

        if ($product->stock < $newQty) {
            return response()->json(['message' => 'Only ' . $product->stock . ' units in stock. Please adjust the quantity.'], 422);
        }

        $cart->update(['quantity' => $newQty]);
        return response()->json(['message' => 'Your cart has been updated.', 'item' => $this->formatItem($cart->load('product.category'))]);
    }

    #[OA\Delete(
        path: '/api/cart/{cart}',
        summary: 'Remove item from cart',
        description: 'Remove a specific item from the cart.',
        tags: ['Cart'],
        operationId: 'cartDestroy',
        parameters: [
            new OA\Parameter(
                name: 'cart',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Cart item ID'
            ),
            new OA\Parameter(
                name: 'guest_token',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Guest token for unauthenticated users'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item removed from cart',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Item removed from cart.'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    // ── DELETE /api/cart/{cart} ──────────────────────────────────────────
    public function destroy(Request $request, Cart $cart)
    {
        if (! $this->owns($request, $cart)) {
            return response()->json(['message' => 'We couldn\'t find what you\'re looking for.'], 404);
        }
        $cart->delete();
        return response()->json(['message' => 'Item removed from your cart']);
    }

    #[OA\Delete(
        path: '/api/cart',
        summary: 'Clear cart',
        description: 'Remove all items from the cart for the authenticated user or guest.',
        tags: ['Cart'],
        operationId: 'cartClear',
        parameters: [
            new OA\Parameter(
                name: 'guest_token',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Guest token for unauthenticated users'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart cleared',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cart cleared.'),
                    ]
                )
            ),
        ]
    )]
    // ── DELETE /api/cart ─────────────────────────────────────────────────
    public function clear(Request $request)
    {
        if ($request->user()) {
            Cart::where('user_id', $request->user()->id)->delete();
        } elseif ($request->filled('guest_token')) {
            Cart::where('guest_token', $request->guest_token)->delete();
        }
        return response()->json(['message' => 'Your cart has been cleared.']);
    }

    #[OA\Post(
        path: '/api/cart/merge',
        summary: 'Merge guest cart into user cart',
        description: 'Merge a guest cart into the authenticated user\'s cart after login.',
        tags: ['Cart'],
        operationId: 'cartMerge',
        security: [
            ['sanctum' => []],
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['guest_token'],
                properties: [
                    new OA\Property(property: 'guest_token', type: 'string', description: 'Guest token to merge'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cart merged successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cart merged successfully.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 400, description: 'Bad request'),
        ]
    )]
    // ── POST /api/cart/merge ─────────────────────────────────────────────
    public function merge(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
        }

        $token = $request->input('guest_token');
        if (! $token) {
            return response()->json(['message' => 'There\'s no guest cart to merge.'], 400);
        }

        $guestItems = Cart::where('guest_token', $token)->get();

        foreach ($guestItems as $guest) {
            $existing = Cart::where('user_id', $user->id)
                ->where('product_id', $guest->product_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guest->quantity);
                $guest->delete();
            } else {
                $guest->update(['user_id' => $user->id, 'guest_token' => null]);
            }
        }

        return response()->json(['message' => 'Your cart has been merged successfully.']);
    }

    // ── Ownership check ──────────────────────────────────────────────────
    private function owns(Request $request, Cart $cart): bool
    {
        if ($request->user() && $cart->user_id === $request->user()->id) return true;
        if ($request->filled('guest_token') && $cart->guest_token === $request->guest_token) return true;
        return false;
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatItem(Cart $cart): array
    {
        $product = $cart->product;
        return [
            'cart_id'  => $cart->id,
            'quantity' => $cart->quantity,
            'status'   => $cart->status ?? 'active',
            'subtotal' => round((float) $product->price * $cart->quantity, 2),
            'product'  => [
                'id'        => $product->id,
                'name'      => $product->name,
                'slug'      => $product->slug,
                'price'     => (float) $product->price,
                'stock'     => $product->stock,
                'image_url' => $product->image ? url('api/storage/' . $product->image) : null,
                'category'  => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            ],
        ];
    }
}
