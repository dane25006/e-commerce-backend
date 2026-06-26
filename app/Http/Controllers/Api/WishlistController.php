<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WishlistController extends Controller
{
    // ── GET /api/wishlist  [auth:sanctum] ────────────────────────────────
    // Returns all wishlisted products for the logged-in user
    public function index(Request $request)
    {
        $items = Wishlist::where('user_id', $request->user()->id)
            ->with('product.category')
            ->latest()
            ->get()
            ->map(fn($w) => [
                'wishlist_id' => $w->id,
                'added_at'    => $w->created_at->toDateTimeString(),
                'product'     => $this->formatProduct($w->product),
            ]);

        return response()->json([
            'wishlist' => $items,
            'count'    => $items->count(),
        ]);
    }

    // ── POST /api/wishlist  [auth:sanctum] ───────────────────────────────
    // Body: { "product_id": 5 }
    // Adds the product. If already wishlisted → returns 200 (idempotent).
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $userId    = $request->user()->id;
        $productId = $request->product_id;

        // Already in wishlist — return existing entry, no error
        $existing = Wishlist::where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->first();

        if ($existing) {
            return response()->json([
                'message'      => 'Already in your wishlist.',
                'wishlist_id'  => $existing->id,
                'wishlisted'   => true,
            ], 200);
        }

        $wishlist = Wishlist::create([
            'user_id'    => $userId,
            'product_id' => $productId,
        ]);

        return response()->json([
            'message'     => 'Added to wishlist.',
            'wishlist_id' => $wishlist->id,
            'wishlisted'  => true,
        ], 201);
    }

    // ── POST /api/wishlist/toggle  [auth:sanctum] ────────────────────────
    // Body: { "product_id": 5 }
    // Adds if not wishlisted, removes if already wishlisted.
    // Vue uses this for the heart button — one endpoint handles both states.
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
        ]);

        $userId    = $request->user()->id;
        $productId = $request->product_id;

        $existing = Wishlist::where('user_id', $userId)
                            ->where('product_id', $productId)
                            ->first();

        if ($existing) {
            // Already wishlisted → remove it
            $existing->delete();

            return response()->json([
                'message'    => 'Removed from wishlist.',
                'wishlisted' => false,
            ]);
        }

        // Not wishlisted → add it
        $wishlist = Wishlist::create([
            'user_id'    => $userId,
            'product_id' => $productId,
        ]);

        return response()->json([
            'message'     => 'Added to wishlist.',
            'wishlist_id' => $wishlist->id,
            'wishlisted'  => true,
        ], 201);
    }

    // ── DELETE /api/wishlist/{wishlist}  [auth:sanctum] ──────────────────
    // Removes a specific wishlist entry by its ID
    public function destroy(Request $request, Wishlist $wishlist)
    {
        // Ownership check — only the owner can remove their wishlist item
        if ($wishlist->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Not found.',
            ], 404);
        }

        $wishlist->delete();

        return response()->json([
            'message'    => 'Removed from wishlist.',
            'wishlisted' => false,
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatProduct(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'slug'        => $product->slug,
            'price'       => (float) $product->price,
            'stock'       => $product->stock,
            'image_url'   => $product->image
                                ? Storage::url($product->image)
                                : null,
            'category'    => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name,
            ] : null,
        ];
    }
}