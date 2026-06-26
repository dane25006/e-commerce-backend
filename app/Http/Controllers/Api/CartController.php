<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
    // ── GET /api/cart  [auth:sanctum] ────────────────────────────────────
    // Returns all cart items for the logged-in user with total
    public function index(Request $request)
    {
        $items = Cart::where('user_id', $request->user()->id)
            ->with('product.category')
            ->get()
            ->map(fn($c) => $this->formatItem($c));

        $subtotal  = $items->sum(fn($i) => $i['subtotal']);
        $itemCount = $items->sum(fn($i) => $i['quantity']);

        return response()->json([
            'cart'       => $items,
            'item_count' => $itemCount,   // total units (e.g. 3 qty = 3)
            'line_count' => $items->count(), // distinct products in cart
            'total'      => round($subtotal, 2),
        ]);
    }

    // ── POST /api/cart  [auth:sanctum] ───────────────────────────────────
    // Body: { "product_id": 3, "quantity": 2 }
    // If the product is already in the cart, quantity is added on top.
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $userId    = $request->user()->id;
        $productId = $request->product_id;
        $quantity  = (int) $request->quantity;

        $product = Product::findOrFail($productId);

        // Product must be in stock
        if ($product->stock === 0) {
            return response()->json([
                'message' => '"' . $product->name . '" is out of stock.',
            ], 422);
        }

        // Check if already in cart
        $existing = Cart::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->first();

        if ($existing) {
            // Adding on top of what's already there
            $newQty = $existing->quantity + $quantity;

            // New total cannot exceed available stock
            if ($product->stock < $newQty) {
                return response()->json([
                    'message' => 'Cannot add ' . $quantity . ' more. '
                               . 'Only ' . ($product->stock - $existing->quantity)
                               . ' more unit(s) available.',
                ], 422);
            }

            $existing->update(['quantity' => $newQty]);
            $cart = $existing->load('product.category');

        } else {
            // First time adding this product
            if ($product->stock < $quantity) {
                return response()->json([
                    'message' => 'Not enough stock. Only '
                               . $product->stock . ' unit(s) available.',
                ], 422);
            }

            $cart = Cart::create([
                'user_id'    => $userId,
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);

            $cart->load('product.category');
        }

        return response()->json([
            'message' => 'Added to cart.',
            'item'    => $this->formatItem($cart),
        ], 201);
    }

    // ── PUT /api/cart/{cart}  [auth:sanctum] ─────────────────────────────
    // Body: { "quantity": 5 }
    // Sets quantity to the exact value given (replaces, does not add).
    public function update(Request $request, Cart $cart)
    {
        // Ownership check
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $newQty  = (int) $request->quantity;
        $product = $cart->product;

        // Cannot set quantity higher than available stock
        if ($product->stock < $newQty) {
            return response()->json([
                'message' => 'Only ' . $product->stock . ' unit(s) in stock.',
            ], 422);
        }

        $cart->update(['quantity' => $newQty]);

        return response()->json([
            'message' => 'Cart updated.',
            'item'    => $this->formatItem($cart->load('product.category')),
        ]);
    }

    // ── DELETE /api/cart/{cart}  [auth:sanctum] ──────────────────────────
    // Removes one specific cart line item
    public function destroy(Request $request, Cart $cart)
    {
        // Ownership check
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $cart->delete();

        return response()->json([
            'message' => 'Item removed from cart.',
        ]);
    }

    // ── DELETE /api/cart  [auth:sanctum] ─────────────────────────────────
    // Clears the entire cart for the logged-in user
    public function clear(Request $request)
    {
        $deleted = Cart::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Cart cleared.',
            'removed' => $deleted,
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatItem(Cart $cart): array
    {
        $product = $cart->product;

        return [
            'cart_id'  => $cart->id,
            'quantity' => $cart->quantity,
            'subtotal' => round((float) $product->price * $cart->quantity, 2),
            'product'  => [
                'id'        => $product->id,
                'name'      => $product->name,
                'slug'      => $product->slug,
                'price'     => (float) $product->price,
                'stock'     => $product->stock,
                'image_url' => $product->image
                                    ? Storage::url($product->image)
                                    : null,
                'category'  => $product->category ? [
                    'id'   => $product->category->id,
                    'name' => $product->category->name,
                ] : null,
            ],
        ];
    }
}