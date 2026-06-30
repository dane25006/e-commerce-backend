<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProductApiController extends Controller
{
    // ── GET /api/categories ──────────────────────────────────────────────
    #[OA\Get(
        path: '/api/categories',
        tags: ['Products'],
    )]
    #[OA\Response(
        response: 200,
        description: 'List of categories with products count',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'categories',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Category')
                ),
            ]
        ),
    )]
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
    #[OA\Get(
        path: '/api/filters',
        tags: ['Products'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Filter options',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'genders', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'brands', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'types', type: 'array', items: new OA\Items(type: 'string')),
                new OA\Property(property: 'departments', type: 'array', items: new OA\Items(type: 'string')),
            ]
        ),
    )]
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
    #[OA\Get(
        path: '/api/products',
        tags: ['Products'],
    )]
    #[OA\Parameter(name: 'search', in: 'query', description: 'Search term', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'category_id', in: 'query', description: 'Category ID', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'gender', in: 'query', description: 'Gender filter (comma-separated)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'brand', in: 'query', description: 'Brand filter (comma-separated)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'type', in: 'query', description: 'Type filter (comma-separated)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'department', in: 'query', description: 'Department filter (comma-separated)', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'max_price', in: 'query', description: 'Maximum price', schema: new OA\Schema(type: 'number'))]
    #[OA\Parameter(name: 'in_stock', in: 'query', description: 'Filter by stock availability', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'is_new', in: 'query', description: 'Filter by new arrivals', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'sort', in: 'query', description: 'Sort order', schema: new OA\Schema(type: 'string', enum: ['newest', 'price_asc', 'price_desc', 'name_asc', 'rating']))]
    #[OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Paginated product list',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'products',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProductListItem')
                ),
                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
            ]
        ),
    )]
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
    #[OA\Get(
        path: '/api/products/{product}',
        tags: ['Products'],
    )]
    #[OA\Parameter(name: 'product', in: 'path', required: true, description: 'Product ID', schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(
        response: 200,
        description: 'Product detail with reviews and related products',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'product', ref: '#/components/schemas/Product'),
            ]
        ),
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
    )]
    public function show(Product $product)
    {
        $product->load(['category', 'reviews.user', 'images']);

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
                                    ? url('api/storage/' . $product->image)
                                    : null,
            'images'      => $product->relationLoaded('images')
                                ? $product->images->map(fn($img) => [
                                    'id'       => $img->id,
                                    'image_url'=> $img->image_url,
                                    'sort_order' => $img->sort_order,
                                ])
                                : [],
            'category'    => $product->category ? [
                'id'   => $product->category->id,
                'name' => $product->category->name,
                'slug' => $product->category->slug,
            ] : null,
            'created_at'  => $product->created_at->toDateTimeString(),
        ];
    }
}
