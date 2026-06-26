<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WishlistController extends Controller
{
    // ── GET /api/wishlist ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Wishlist::with('product.category');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } elseif ($request->filled('guest_token')) {
            $query->where('guest_token', $request->guest_token);
        } else {
            return response()->json(['wishlist' => [], 'count' => 0]);
        }

        $items = $query->latest()->get()->map(fn($w) => [
            'wishlist_id' => $w->id,
            'added_at'    => $w->created_at->toDateTimeString(),
            'product'     => $this->formatProduct($w->product),
        ]);

        return response()->json(['wishlist' => $items, 'count' => $items->count()]);
    }

    // ── POST /api/wishlist ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $productId = $request->product_id;
        $ownerId   = $request->user() ? $request->user()->id : null;
        $token     = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $query = Wishlist::where('product_id', $productId);
        if ($ownerId) {
            $query->where('user_id', $ownerId);
        } else {
            $query->where('guest_token', $token);
        }
        $existing = $query->first();

        if ($existing) {
            return response()->json(['message' => 'Already in your wishlist.', 'wishlist_id' => $existing->id, 'wishlisted' => true], 200);
        }

        $data = ['product_id' => $productId];
        if ($ownerId) {
            $data['user_id'] = $ownerId;
        } else {
            $data['guest_token'] = $token;
        }
        $wishlist = Wishlist::create($data);

        return response()->json(['message' => 'Added to wishlist.', 'wishlist_id' => $wishlist->id, 'wishlisted' => true], 201);
    }

    // ── POST /api/wishlist/toggle ─────────────────────────────────────────
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $productId = $request->product_id;
        $ownerId   = $request->user() ? $request->user()->id : null;
        $token     = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $query = Wishlist::where('product_id', $productId);
        if ($ownerId) {
            $query->where('user_id', $ownerId);
        } else {
            $query->where('guest_token', $token);
        }
        $existing = $query->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['message' => 'Removed from wishlist.', 'wishlisted' => false]);
        }

        $data = ['product_id' => $productId];
        if ($ownerId) {
            $data['user_id'] = $ownerId;
        } else {
            $data['guest_token'] = $token;
        }
        Wishlist::create($data);

        return response()->json(['message' => 'Added to wishlist.', 'wishlisted' => true], 201);
    }

    // ── DELETE /api/wishlist/{wishlist} ───────────────────────────────────
    public function destroy(Request $request, Wishlist $wishlist)
    {
        if (! $this->owns($request, $wishlist)) {
            return response()->json(['message' => 'Not found.'], 404);
        }
        $wishlist->delete();
        return response()->json(['message' => 'Removed from wishlist.', 'wishlisted' => false]);
    }

    // ── POST /api/wishlist/merge ─────────────────────────────────────────
    public function merge(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $token = $request->input('guest_token');
        if (! $token) {
            return response()->json(['message' => 'No guest wishlist to merge.'], 400);
        }

        $guestItems = Wishlist::where('guest_token', $token)->get();

        foreach ($guestItems as $guest) {
            $existing = Wishlist::where('user_id', $user->id)
                ->where('product_id', $guest->product_id)
                ->first();

            if (! $existing) {
                $guest->update(['user_id' => $user->id, 'guest_token' => null]);
            } else {
                $guest->delete();
            }
        }

        return response()->json(['message' => 'Wishlist merged successfully.']);
    }

    // ── Ownership check ──────────────────────────────────────────────────
    private function owns(Request $request, Wishlist $wishlist): bool
    {
        if ($request->user() && $wishlist->user_id === $request->user()->id) return true;
        if ($request->filled('guest_token') && $wishlist->guest_token === $request->guest_token) return true;
        return false;
    }

    private function formatProduct(?Product $product): ?array
    {
        if (! $product) return null;
        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'slug'        => $product->slug,
            'description' => $product->description,
            'price'       => (float) $product->price,
            'stock'       => $product->stock,
            'image_url'   => $product->image ? Storage::url($product->image) : null,
            'category'    => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name, 'slug' => $product->category->slug] : null,
            'created_at'  => $product->created_at->toDateTimeString(),
        ];
    }
}
