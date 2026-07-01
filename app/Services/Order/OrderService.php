<?php

namespace App\Services\Order;

use App\Models\{Cart, Order, OrderItem, Product};
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(int $userId, string $shippingAddress, string $paymentMethod): Order
    {
        $cartItems = Cart::where('user_id', $userId)
            ->with('product')
            ->get();

        $order = DB::transaction(function () use ($userId, $cartItems, $shippingAddress, $paymentMethod) {
            $total = $cartItems->sum(fn($c) => (float) $c->product->price * $c->quantity);

            $order = Order::create([
                'user_id'          => $userId,
                'total'            => $total,
                'status'           => 'pending',
                'shipping_address' => $shippingAddress,
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

            Cart::where('user_id', $userId)->delete();

            return $order;
        });

        return $order->load('items.product');
    }

    public function cancelOrder(Order $order): Order
    {
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });

        return $order->fresh();
    }

    /** @return array{id: int, status: string, total: float, items_count: int, created_at: string} */
    public function formatOrderSummary(Order $order): array
    {
        return [
            'id'           => $order->id,
            'status'       => $order->status,
            'total'        => (float) $order->total,
            'items_count'  => $order->items_count ?? $order->items->sum('quantity'),
            'created_at'   => $order->created_at->toDateTimeString(),
        ];
    }

    /** @return array{id: int, status: string, total: float, shipping_address: string, payment_method: string, created_at: string, updated_at: string, items: array} */
    public function formatOrderDetail(Order $order): array
    {
        return [
            'id'               => $order->id,
            'status'           => $order->status,
            'total'            => (float) $order->total,
            'shipping_address' => $order->shipping_address,
            'payment_method'   => $order->payment_method,
            'created_at'       => $order->created_at->toDateTimeString(),
            'updated_at'       => $order->updated_at->toDateTimeString(),
            'items'            => $order->items->map(fn($item) => [
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
            ])->values()->toArray(),
        ];
    }
}
