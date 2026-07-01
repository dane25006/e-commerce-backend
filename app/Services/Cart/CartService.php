<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;

class CartService
{
    /** @return array{cart_id: int, quantity: int, status: string, subtotal: float, product: array} */
    public function formatItem(Cart $cart): array
    {
        $product = $cart->product;

        if (! $product) {
            return [
                'cart_id'  => $cart->id,
                'quantity' => $cart->quantity,
                'status'   => $cart->status ?? 'active',
                'subtotal' => 0,
                'product'  => [
                    'id'        => null,
                    'name'      => 'Unavailable Product',
                    'slug'      => '',
                    'price'     => 0,
                    'stock'     => 0,
                    'image_url' => null,
                    'category'  => null,
                ],
            ];
        }

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
                'category'  => $product->category
                    ? ['id' => $product->category->id, 'name' => $product->category->name]
                    : null,
            ],
        ];
    }

    public function getUserCart(?int $userId, ?string $guestToken): array
    {
        $query = Cart::with('product.category');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($guestToken) {
            $query->where('guest_token', $guestToken);
        } else {
            return ['cart' => [], 'item_count' => 0, 'line_count' => 0, 'total' => 0];
        }

        $items     = $query->get()->map(fn($c) => $this->formatItem($c));
        $subtotal  = $items->sum(fn($i) => $i['subtotal']);
        $itemCount = $items->sum(fn($i) => $i['quantity']);

        return [
            'cart'       => $items->values()->toArray(),
            'item_count' => $itemCount,
            'line_count' => $items->count(),
            'total'      => round($subtotal, 2),
        ];
    }

    public function addItem(?int $userId, ?string $guestToken, int $productId, int $quantity): Cart
    {
        $product = Product::findOrFail($productId);

        $query = Cart::where('product_id', $productId);
        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('guest_token', $guestToken);
        }

        $existing = $query->first();

        if ($existing) {
            $newQty = $existing->quantity + $quantity;
            $existing->update(['quantity' => $newQty]);
            return $existing->load('product.category');
        }

        $data = [
            'product_id' => $productId,
            'quantity'   => $quantity,
        ];

        if ($userId) {
            $data['user_id'] = $userId;
        } else {
            $data['guest_token'] = $guestToken;
        }

        $cart = Cart::create($data);
        return $cart->load('product.category');
    }

    public function mergeGuestCart(int $userId, string $guestToken): void
    {
        $guestItems = Cart::where('guest_token', $guestToken)->get();

        foreach ($guestItems as $guest) {
            $existing = Cart::where('user_id', $userId)
                ->where('product_id', $guest->product_id)
                ->first();

            if ($existing) {
                $existing->increment('quantity', $guest->quantity);
                $guest->delete();
            } else {
                $guest->update(['user_id' => $userId, 'guest_token' => null]);
            }
        }
    }
}
