<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CartController extends Controller
{
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
            return response()->json(['message' => '"' . $product->name . '" is out of stock.'], 422);
        }

        $ownerId = $request->user() ? $request->user()->id : null;
        $token   = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Authentication required.'], 401);
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
                    'message' => 'Cannot add ' . $quantity . ' more. Only '
                        . ($product->stock - $existing->quantity) . ' more available.',
                ], 422);
            }
            $existing->update(['quantity' => $newQty]);
            $cart = $existing->load('product.category');
        } else {
            if ($product->stock < $quantity) {
                return response()->json([
                    'message' => 'Not enough stock. Only ' . $product->stock . ' available.',
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

        return response()->json(['message' => 'Added to cart.', 'item' => $this->formatItem($cart)], 201);
    }

    // ── PUT /api/cart/{cart} ─────────────────────────────────────────────
    public function update(Request $request, Cart $cart)
    {
        if (! $this->owns($request, $cart)) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);
        $newQty  = (int) $request->quantity;
        $product = $cart->product;

        if ($product->stock < $newQty) {
            return response()->json(['message' => 'Only ' . $product->stock . ' in stock.'], 422);
        }

        $cart->update(['quantity' => $newQty]);
        return response()->json(['message' => 'Cart updated.', 'item' => $this->formatItem($cart->load('product.category'))]);
    }

    // ── DELETE /api/cart/{cart} ──────────────────────────────────────────
    public function destroy(Request $request, Cart $cart)
    {
        if (! $this->owns($request, $cart)) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $cart->delete();
        return response()->json(['message' => 'Item removed from cart.']);
    }

    // ── DELETE /api/cart ─────────────────────────────────────────────────
    public function clear(Request $request)
    {
        if ($request->user()) {
            Cart::where('user_id', $request->user()->id)->delete();
        } elseif ($request->filled('guest_token')) {
            Cart::where('guest_token', $request->guest_token)->delete();
        }
        return response()->json(['message' => 'Cart cleared.']);
    }

    // ── POST /api/cart/merge ─────────────────────────────────────────────
    public function merge(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $request->input('guest_token');
        if (! $token) {
            return response()->json(['message' => 'No guest cart to merge.'], 400);
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

        return response()->json(['message' => 'Cart merged successfully.']);
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
            'subtotal' => round((float) $product->price * $cart->quantity, 2),
            'product'  => [
                'id'        => $product->id,
                'name'      => $product->name,
                'slug'      => $product->slug,
                'price'     => (float) $product->price,
                'stock'     => $product->stock,
                'image_url' => $product->image ? Storage::url($product->image) : null,
                'category'  => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name] : null,
            ],
        ];
    }
}
