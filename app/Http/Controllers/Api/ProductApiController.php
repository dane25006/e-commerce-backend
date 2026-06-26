<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    // ── GET /api/categories ──────────────────────────────────────────────
    public function categories()
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get()
            ->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'slug'           => $c->slug,
                'products_count' => $c->products_count,
            ]);

        return response()->json([
            'categories' => $categories,
        ]);
    }

    // ── GET /api/products ────────────────────────────────────────────────
    // Supports:
    //   ?search=phone          → search name + description
    //   ?category_id=2         → filter by category
    //   ?min_price=10          → price range floor
    //   ?max_price=200         → price range ceiling
    //   ?sort=newest           → newest (default)
    //   ?sort=price_asc        → cheapest first
    //   ?sort=price_desc       → most expensive first
    //   ?sort=name_asc         → A–Z
    //   ?per_page=12           → items per page (max 50)
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Only show in-stock products to customers
        $query->where('stock', '>', 0);

        // Search by name or description
        if ($request->filled('search')) {
            $term = '%' . $request->search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Sorting
        match ($request->get('sort', 'newest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            default      => $query->latest(),  // newest
        };

        $perPage  = min((int) $request->get('per_page', 12), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'products' => $products->through(fn($p) => $this->formatProduct($p)),
            'meta'     => [
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
            ],
        ]);
    }

    // ── GET /api/products/{product} ──────────────────────────────────────
    public function show(Product $product)
    {
        $product->load(['category', 'reviews.user']);

        $formatted = $this->formatProduct($product);

        // Rating summary
        $formatted['rating_avg']   = $product->reviews->count()
            ? round($product->reviews->avg('rating'), 1)
            : null;
        $formatted['rating_count'] = $product->reviews->count();

        // Latest 10 reviews
        $formatted['reviews'] = $product->reviews
            ->sortByDesc('created_at')
            ->take(10)
            ->map(fn($r) => [
                'id'         => $r->id,
                'user_name'  => $r->user?->name ?? 'Anonymous',
                'rating'     => $r->rating,
                'comment'    => $r->comment,
                'created_at' => $r->created_at->toDateTimeString(),
            ])
            ->values();

        // Related products — same category, exclude self, max 4
        $formatted['related'] = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('stock', '>', 0)
            ->latest()
            ->take(4)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'price'     => (float) $p->price,
                'image_url' => $p->image ? Storage::url($p->image) : null,
            ]);

        return response()->json([
            'product' => $formatted,
        ]);
    }

    // ── Private helper ────────────────────────────────────────────────────
    private function formatProduct(Product $product): array
    {
        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'slug'        => $product->slug,
            'description' => $product->description,
            'price'       => (float) $product->price,
            'stock'       => $product->stock,
            'image_url'   => $product->image
                                ? Storage::url($product->image)
                                : null,
            'category'    => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'created_at'  => $product->created_at->toDateTimeString(),
        ];
    }
}