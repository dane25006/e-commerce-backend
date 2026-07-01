<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CartStoreRequest;
use App\Http\Responses\ApiResponse;
use App\Models\Cart;
use App\Models\Product;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CartService $cartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user  = $request->user('sanctum');
        $token = $request->input('guest_token');

        $result = $this->cartService->getUserCart(
            $user?->id,
            $user ? null : $token
        );

        return response()->json($result);
    }

    public function store(CartStoreRequest $request): JsonResponse
    {
        $user       = $request->user('sanctum');
        $guestToken = $request->input('guest_token');

        if (! $user && ! $guestToken) {
            return $this->unauthorized('Please sign in to continue.');
        }

        $product = Product::findOrFail($request->product_id);

        if ($product->stock === 0) {
            return $this->unprocessable($product->name . ' is currently out of stock.');
        }

        $newQty = $request->quantity;
        $existing = null;

        if ($user) {
            $existing = Cart::where('product_id', $request->product_id)
                ->where('user_id', $user->id)
                ->first();
        } else {
            $existing = Cart::where('product_id', $request->product_id)
                ->where('guest_token', $guestToken)
                ->first();
        }

        if ($existing) {
            $newQty += $existing->quantity;
        }

        if ($product->stock < $newQty) {
            return $this->unprocessable(
                $existing
                    ? 'We only have ' . ($product->stock - $existing->quantity) . ' more of this item in stock.'
                    : 'Only ' . $product->stock . ' units available. Please adjust the quantity.'
            );
        }

        $cart = $this->cartService->addItem(
            $user?->id,
            $user ? null : $guestToken,
            $request->product_id,
            $request->quantity
        );

        return $this->created([
            'item' => $this->cartService->formatItem($cart),
        ], 'Added to your cart');
    }

    public function update(Request $request, Cart $cart): JsonResponse
    {
        if (! $this->owns($request, $cart)) {
            return $this->notFound("We couldn't find what you're looking for.");
        }

        $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:100']]);

        $product = $cart->product;

        if ($product->stock < $request->quantity) {
            return $this->unprocessable(
                'Only ' . $product->stock . ' units in stock. Please adjust the quantity.'
            );
        }

        $cart->update(['quantity' => $request->quantity]);

        return $this->success([
            'item' => $this->cartService->formatItem($cart->load('product.category')),
        ], 'Your cart has been updated.');
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        if (! $this->owns($request, $cart)) {
            return $this->notFound("We couldn't find what you're looking for.");
        }

        $cart->delete();

        return $this->success(null, 'Item removed from your cart');
    }

    public function clear(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');

        if ($user) {
            Cart::where('user_id', $user->id)->delete();
        } elseif ($request->filled('guest_token')) {
            Cart::where('guest_token', $request->guest_token)->delete();
        }

        return $this->success(null, 'Your cart has been cleared.');
    }

    public function merge(Request $request): JsonResponse
    {
        $user  = $request->user('sanctum');
        $token = $request->input('guest_token');

        if (! $user) {
            return $this->unauthorized('Please sign in to continue.');
        }

        if (! $token) {
            return $this->error("There's no guest cart to merge.", 400);
        }

        $this->cartService->mergeGuestCart($user->id, $token);

        return $this->success(null, 'Your cart has been merged successfully.');
    }

    private function owns(Request $request, Cart $cart): bool
    {
        if ($request->user('sanctum') && $cart->user_id === $request->user('sanctum')->id) return true;
        if ($request->filled('guest_token') && $cart->guest_token === $request->guest_token) return true;
        return false;
    }
}
