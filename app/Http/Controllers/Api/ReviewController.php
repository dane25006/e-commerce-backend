<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Product, Review};
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ReviewController extends Controller
{
    #[OA\Get(
        path: '/api/products/{product}/reviews',
        summary: 'List product reviews',
        description: 'Retrieve paginated reviews for a specific product. Publicly accessible.',
        tags: ['Reviews'],
        operationId: 'reviewIndex',
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Product ID'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful operation',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'product_id', type: 'integer'),
                        new OA\Property(property: 'product_name', type: 'string'),
                        new OA\Property(property: 'rating_avg', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'rating_count', type: 'integer'),
                        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(ref: '#/components/schemas/Review')),
                        new OA\Property(property: 'meta', properties: [
                            new OA\Property(property: 'current_page', type: 'integer'),
                            new OA\Property(property: 'last_page', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Product not found'),
        ]
    )]
    // ── GET /api/products/{product}/reviews  [public] ────────────────────
    // Anyone can read reviews — no token required
    public function index(Product $product)
    {
        $reviews = Review::where('product_id', $product->id)
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        $avgRating = Review::where('product_id', $product->id)->avg('rating');

        return response()->json([
            'product_id'  => $product->id,
            'product_name'=> $product->name,
            'rating_avg'  => $avgRating ? round($avgRating, 1) : null,
            'rating_count'=> Review::where('product_id', $product->id)->count(),
            'reviews'     => $reviews->through(fn($r) => $this->formatReview($r)),
            'meta'        => [
                'current_page' => $reviews->currentPage(),
                'last_page'    => $reviews->lastPage(),
                'total'        => $reviews->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/products/{product}/reviews',
        summary: 'Create a review',
        description: 'Submit a rating and comment for a product. One review per user per product.',
        tags: ['Reviews'],
        operationId: 'reviewStore',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Product ID'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', description: 'Rating 1-5', minimum: 1, maximum: 5),
                    new OA\Property(property: 'comment', type: 'string', description: 'Review comment', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Review submitted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'review', ref: '#/components/schemas/Review'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── POST /api/products/{product}/reviews  [auth:sanctum] ─────────────
    // Body: { "rating": 5, "comment": "Great product!" }
    // One review per user per product — enforced here and in DB unique index.
    public function store(Request $request, Product $product)
    {
        $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = $request->user()->id;

        // Check for existing review
        $existing = Review::where('user_id', $userId)
                          ->where('product_id', $product->id)
                          ->first();

        if ($existing) {
            return response()->json([
                'message' => 'You\'ve already reviewed this product. You can edit your existing review instead.',
                'review'  => $this->formatReview($existing->load('user')),
            ], 422);
        }

        $review = Review::create([
            'user_id'    => $userId,
            'product_id' => $product->id,
            'rating'     => $request->rating,
            'comment'    => $request->comment,
        ]);

        $review->load('user');

        return response()->json([
            'message' => 'Thank you! Your review has been submitted.',
            'review'  => $this->formatReview($review),
        ], 201);
    }

    #[OA\Put(
        path: '/api/products/{product}/reviews/{review}',
        summary: 'Update a review',
        description: 'Update the rating and/or comment of your own review.',
        tags: ['Reviews'],
        operationId: 'reviewUpdate',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Product ID'
            ),
            new OA\Parameter(
                name: 'review',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Review ID'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['rating'],
                properties: [
                    new OA\Property(property: 'rating', type: 'integer', description: 'Rating 1-5', minimum: 1, maximum: 5),
                    new OA\Property(property: 'comment', type: 'string', description: 'Review comment', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Review updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'review', ref: '#/components/schemas/Review'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not your review'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── PUT /api/products/{product}/reviews/{review}  [auth:sanctum] ─────
    // Body: { "rating": 4, "comment": "Updated opinion" }
    // Users can edit only their own review.
    public function update(Request $request, Product $product, Review $review)
    {
        // Ownership check
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only edit your own reviews.',
            ], 403);
        }

        // Make sure the review belongs to this product
        if ($review->product_id !== $product->id) {
            return response()->json([
                'message' => 'This review doesn\'t belong to this product.',
            ], 422);
        }

        $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update([
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user');

        return response()->json([
            'message' => 'Your review has been updated.',
            'review'  => $this->formatReview($review),
        ]);
    }

    #[OA\Delete(
        path: '/api/products/{product}/reviews/{review}',
        summary: 'Delete a review',
        description: 'Delete your own review. Only the review author can delete.',
        tags: ['Reviews'],
        operationId: 'reviewDestroy',
        security: [
            ['sanctum' => []],
        ],
        parameters: [
            new OA\Parameter(
                name: 'product',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Product ID'
            ),
            new OA\Parameter(
                name: 'review',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                description: 'Review ID'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Review deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Your review has been deleted.'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — not your review'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    // ── DELETE /api/products/{product}/reviews/{review}  [auth:sanctum] ──
    // Users can delete only their own review.
    public function destroy(Request $request, Product $product, Review $review)
    {
        // Ownership check
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You can only delete your own reviews.',
            ], 403);
        }

        // Make sure the review belongs to this product
        if ($review->product_id !== $product->id) {
            return response()->json([
                'message' => 'This review doesn\'t belong to this product.',
            ], 422);
        }

        $review->delete();

        return response()->json([
            'message' => 'Your review has been deleted.',
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatReview(Review $review): array
    {
        return [
            'id'         => $review->id,
            'user_id'    => $review->user_id,
            'user_name'  => $review->user?->name ?? 'Anonymous',
            'rating'     => $review->rating,
            'comment'    => $review->comment,
            'created_at' => $review->created_at->toDateTimeString(),
            'updated_at' => $review->updated_at->toDateTimeString(),
        ];
    }
}