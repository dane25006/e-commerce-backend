<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class WishlistController extends Controller
{
    #[OA\Get(
        path: '/api/wishlist',
        summary: 'Get wishlist items',
        description: 'Retrieve all items in the wishlist for the authenticated user or guest.',
        tags: ['Wishlist'],
        operationId: 'wishlistIndex',
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
                        new OA\Property(property: 'wishlist', type: 'array', items: new OA\Items(ref: '#/components/schemas/WishlistItem')),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    // ── GET /api/wishlist ─────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Wishlist::with('product.category');

        if ($request->user('sanctum')) {
            $query->where('user_id', $request->user('sanctum')->id);
        } elseif ($request->filled('guest_token')) {
            $query->where('guest_token', $request->guest_token);
        } else {
            return response()->json(['wishlist' => [], 'count' => 0]);
        }

        $items = $query->latest()->get()->map(fn($w) => [
            'wishlist_id' => $w->id,
            'status'      => $w->status ?? 'active',
            'added_at'    => $w->created_at->toDateTimeString(),
            'product'     => $this->formatProduct($w->product),
        ]);

        return response()->json(['wishlist' => $items, 'count' => $items->count()]);
    }

    #[OA\Post(
        path: '/api/wishlist',
        summary: 'Add item to wishlist',
        description: 'Add a product to the wishlist.',
        tags: ['Wishlist'],
        operationId: 'wishlistStore',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', description: 'Product ID'),
                    new OA\Property(property: 'guest_token', type: 'string', description: 'Guest token for unauthenticated users'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Item added to wishlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'wishlist_id', type: 'integer'),
                        new OA\Property(property: 'wishlisted', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(response: 200, description: 'Item already in wishlist'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/wishlist ────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $productId = $request->product_id;
        $ownerId   = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        $token     = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
        }

        $query = Wishlist::where('product_id', $productId);
        if ($ownerId) {
            $query->where('user_id', $ownerId);
        } else {
            $query->where('guest_token', $token);
        }
        $existing = $query->first();

        if ($existing) {
            return response()->json(['message' => 'This item is already in your wishlist', 'wishlist_id' => $existing->id, 'wishlisted' => true], 200);
        }

        $data = ['product_id' => $productId];
        if ($ownerId) {
            $data['user_id'] = $ownerId;
        } else {
            $data['guest_token'] = $token;
        }
        $wishlist = Wishlist::create($data);

        return response()->json(['message' => 'Saved to your wishlist', 'wishlist_id' => $wishlist->id, 'wishlisted' => true], 201);
    }

    #[OA\Post(
        path: '/api/wishlist/toggle',
        summary: 'Toggle wishlist item',
        description: 'Add or remove a product from the wishlist. If it exists, remove it; if not, add it.',
        tags: ['Wishlist'],
        operationId: 'wishlistToggle',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', description: 'Product ID'),
                    new OA\Property(property: 'guest_token', type: 'string', description: 'Guest token for unauthenticated users'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item removed from wishlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'wishlisted', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(
                response: 201,
                description: 'Item added to wishlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'wishlisted', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/wishlist/toggle ─────────────────────────────────────────
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => ['required', 'exists:products,id']]);

        $productId = $request->product_id;
        $ownerId   = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        $token     = $ownerId ? null : $request->input('guest_token');

        if (! $ownerId && ! $token) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
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
            return response()->json(['message' => 'Removed from your wishlist', 'wishlisted' => false]);
        }

        $data = ['product_id' => $productId];
        if ($ownerId) {
            $data['user_id'] = $ownerId;
        } else {
            $data['guest_token'] = $token;
        }
        Wishlist::create($data);

        return response()->json(['message' => 'Saved to your wishlist', 'wishlisted' => true], 201);
    }

    #[OA\Delete(
        path: '/api/wishlist/{wishlist}',
        summary: 'Remove item from wishlist',
        description: 'Remove a specific item from the wishlist.',
        tags: ['Wishlist'],
        operationId: 'wishlistDestroy',
        parameters: [
            new OA\Parameter(
                name: 'wishlist',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Wishlist item ID'
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
                description: 'Item removed from wishlist',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Removed from your wishlist'),
                        new OA\Property(property: 'wishlisted', type: 'boolean'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    // ── DELETE /api/wishlist/{wishlist} ───────────────────────────────────
    public function destroy(Request $request, Wishlist $wishlist)
    {
        if (! $this->owns($request, $wishlist)) {
            return response()->json(['message' => "We couldn't find that item."], 404);
        }
        $wishlist->delete();
        return response()->json(['message' => 'Removed from your wishlist', 'wishlisted' => false]);
    }

    #[OA\Post(
        path: '/api/wishlist/merge',
        summary: 'Merge guest wishlist into user wishlist',
        description: 'Merge a guest wishlist into the authenticated user\'s wishlist after login.',
        tags: ['Wishlist'],
        operationId: 'wishlistMerge',
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
                description: 'Wishlist merged successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Your wishlist has been merged.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 400, description: 'Bad request'),
        ]
    )]
    // ── POST /api/wishlist/merge ─────────────────────────────────────────
    public function merge(Request $request)
    {
        $user = $request->user('sanctum');
        if (! $user) {
            return response()->json(['message' => 'Please sign in to continue.'], 401);
        }

        $token = $request->input('guest_token');
        if (! $token) {
            return response()->json(['message' => "There's no guest wishlist to merge."], 400);
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

        return response()->json(['message' => 'Your wishlist has been merged.']);
    }

    // ── Ownership check ──────────────────────────────────────────────────
    private function owns(Request $request, Wishlist $wishlist): bool
    {
        if ($request->user('sanctum') && $wishlist->user_id === $request->user('sanctum')->id) return true;
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
            'image_url'   => $product->image ? url('api/storage/' . $product->image) : null,
            'category'    => $product->category ? ['id' => $product->category->id, 'name' => $product->category->name, 'slug' => $product->category->slug] : null,
            'created_at'  => $product->created_at->toDateTimeString(),
        ];
    }
}
