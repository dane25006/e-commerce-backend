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

    // ── GET /api/filters ─────────────────────────────────────────────────
    public function filters()
    {
        return response()->json([
            'genders'    => Product::whereNotNull('gender')->distinct()->pluck('gender'),
            'brands'     => Product::whereNotNull('brand')->distinct()->pluck('brand'),
            'types'      => Product::whereNotNull('type')->distinct()->pluck('type'),
            'departments'=> Product::whereNotNull('department')->distinct()->pluck('department'),
        ]);
    }

    // ── GET /api/products ────────────────────────────────────────────────
    public function index(Request $request)
    {
        $query = Product::with('category')
            ->withAvg('reviews', 'rating');

        // In-stock filter — default true, pass in_stock=false to show all
        $inStockOnly = $request->has('in_stock') ? $request->boolean('in_stock') : true;
        if ($inStockOnly) {
            $query->where('stock', '>', 0);
        }

        // New arrivals filter
        if ($request->filled('is_new')) {
            $query->where('is_new', $request->boolean('is_new'));
        }

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

        // Multi-value filters (comma-separated)
        foreach (['gender', 'brand', 'type', 'department'] as $field) {
            if ($request->filled($field)) {
                $values = array_map('trim', explode(',', $request->$field));
                $query->whereIn($field, $values);
            }
        }

        // Price range filter
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Sorting
        match ($request->get('sort', 'newest')) {
            'price_asc'  => $query->orderBy('price', 'asc'),
            'price_desc' => $query->orderBy('price', 'desc'),
            'name_asc'   => $query->orderBy('name', 'asc'),
            'rating'     => $query->orderBy('reviews_avg_rating', 'desc'),
            default      => $query->latest(),
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

        $formatted['rating_avg']   = $product->reviews->count()
            ? round($product->reviews->avg('rating'), 1)
            : null;
        $formatted['rating_count'] = $product->reviews->count();

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
            'rating_avg'  => $product->reviews_avg_rating
                ? round((float) $product->reviews_avg_rating, 1)
                : null,
            'name'        => $product->name,
            'slug'        => $product->slug,
            'description' => $product->description,
            'price'       => (float) $product->price,
            'sale_price'  => $product->sale_price ? (float) $product->sale_price : null,
            'stock'       => $product->stock,
            'is_new'      => $product->is_new,
            'gender'      => $product->gender,
            'brand'       => $product->brand,
            'type'        => $product->type,
            'department'  => $product->department,
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
