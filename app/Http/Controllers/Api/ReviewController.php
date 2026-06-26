<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Product, Review};
use Illuminate\Http\Request;

class ReviewController extends Controller
{
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
                'message' => 'You have already reviewed this product. Use PUT to update your review.',
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
            'message' => 'Review submitted successfully.',
            'review'  => $this->formatReview($review),
        ], 201);
    }

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
                'message' => 'Review does not belong to this product.',
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
            'message' => 'Review updated successfully.',
            'review'  => $this->formatReview($review),
        ]);
    }

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
                'message' => 'Review does not belong to this product.',
            ], 422);
        }

        $review->delete();

        return response()->json([
            'message' => 'Review deleted.',
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