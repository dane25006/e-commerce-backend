<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'My API Documentation',
    description: 'Complete API documentation for the E-commerce platform covering authentication, products, categories, cart, wishlist, orders, reviews, and promotions.',
    contact: new OA\Contact(email: 'admin@example.com'),
    license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT')
)]
#[OA\Server(
    url: 'http://localhost:8000',
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Enter token in format: Bearer <token>'
)]

// ── User ──────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
        new OA\Property(property: 'role', type: 'string', example: 'customer'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── Category ──────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'Category',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Floral'),
        new OA\Property(property: 'slug', type: 'string', example: 'floral'),
        new OA\Property(property: 'products_count', type: 'integer', example: 12),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── Product ───────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'Product',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
        new OA\Property(property: 'slug', type: 'string', example: 'rose-velvet'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'A luxurious floral fragrance with notes of rose, musk, and vanilla.'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
        new OA\Property(property: 'sale_price', type: 'number', format: 'float', nullable: true, example: 149.99),
        new OA\Property(property: 'stock', type: 'integer', example: 25),
        new OA\Property(property: 'is_new', type: 'boolean', example: true),
        new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'female'),
        new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'Chanel'),
        new OA\Property(property: 'type', type: 'string', nullable: true, example: 'Eau de Parfum'),
        new OA\Property(property: 'department', type: 'string', nullable: true, example: 'luxury'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/products/rose-velvet.jpg'),
        new OA\Property(property: 'rating_avg', type: 'number', format: 'float', nullable: true, example: 4.5),
        new OA\Property(property: 'rating_count', type: 'integer', example: 28),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'reviews', type: 'array', items: new OA\Items(ref: '#/components/schemas/Review')),
        new OA\Property(property: 'related', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── ProductListItem (used in product listings) ────────────────────────
#[OA\Schema(
    schema: 'ProductListItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
        new OA\Property(property: 'slug', type: 'string', example: 'rose-velvet'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'A luxurious floral fragrance.'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
        new OA\Property(property: 'sale_price', type: 'number', format: 'float', nullable: true, example: 149.99),
        new OA\Property(property: 'stock', type: 'integer', example: 25),
        new OA\Property(property: 'is_new', type: 'boolean', example: true),
        new OA\Property(property: 'gender', type: 'string', nullable: true, example: 'female'),
        new OA\Property(property: 'brand', type: 'string', nullable: true, example: 'Chanel'),
        new OA\Property(property: 'type', type: 'string', nullable: true, example: 'Eau de Parfum'),
        new OA\Property(property: 'department', type: 'string', nullable: true, example: 'luxury'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/products/rose-velvet.jpg'),
        new OA\Property(property: 'rating_avg', type: 'number', format: 'float', nullable: true, example: 4.5),
        new OA\Property(property: 'category', ref: '#/components/schemas/Category'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── CartItem ──────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'CartItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'cart_id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 379.98),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'product', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
            new OA\Property(property: 'slug', type: 'string', example: 'rose-velvet'),
            new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
            new OA\Property(property: 'stock', type: 'integer', example: 25),
            new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/products/rose-velvet.jpg'),
            new OA\Property(property: 'category', type: 'object', properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Floral'),
            ]),
        ]),
    ]
)]

// ── WishlistItem ──────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'WishlistItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'wishlist_id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
        new OA\Property(property: 'added_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'product', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
            new OA\Property(property: 'slug', type: 'string', example: 'rose-velvet'),
            new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
            new OA\Property(property: 'stock', type: 'integer', example: 25),
            new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/products/rose-velvet.jpg'),
        ]),
    ]
)]

// ── Order ─────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'Order',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 379.98),
        new OA\Property(property: 'shipping_address', type: 'string', example: '123 Main St, New York, NY 10001'),
        new OA\Property(property: 'payment_method', type: 'string', example: 'credit_card'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/OrderItem')),
    ]
)]

// ── OrderItem ─────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'OrderItem',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 379.98),
        new OA\Property(property: 'product', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
            new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/products/rose-velvet.jpg'),
        ]),
    ]
)]

// ── Review ────────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'Review',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_name', type: 'string', example: 'Jane D.'),
        new OA\Property(property: 'rating', type: 'integer', example: 5),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Absolutely stunning fragrance!'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── Promotion ─────────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'Promotion',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Summer Sale'),
        new OA\Property(property: 'slug', type: 'string', example: 'summer-sale'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Get 20% off on all floral fragrances.'),
        new OA\Property(property: 'discount_type', type: 'string', example: 'percentage'),
        new OA\Property(property: 'discount_value', type: 'number', format: 'float', example: 20),
        new OA\Property(property: 'coupon_code', type: 'string', nullable: true, example: 'SUMMER20'),
        new OA\Property(property: 'image_url', type: 'string', nullable: true, example: 'http://localhost:8000/storage/promotions/summer.jpg'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true, example: '2025-06-01T00:00:00.000000Z'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2025-06-30T23:59:59.000000Z'),
        new OA\Property(property: 'product', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Rose Velvet'),
            new OA\Property(property: 'price', type: 'number', format: 'float', example: 189.99),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2025-01-15T10:30:00.000000Z'),
    ]
)]

// ── Pagination Meta ───────────────────────────────────────────────────
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 5),
        new OA\Property(property: 'total', type: 'integer', example: 50),
        new OA\Property(property: 'per_page', type: 'integer', example: 12),
    ]
)]

// ── Error Schemas ─────────────────────────────────────────────────────
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'Unauthenticated',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Please sign in to continue.'),
    ]
)]
#[OA\Schema(
    schema: 'NotFound',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'We couldn\u2019t find what you\u2019re looking for.'),
    ]
)]
#[OA\Schema(
    schema: 'ServerError',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'An unexpected error occurred. Please try again later.'),
    ]
)]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully.'),
    ]
)]

class OpenApi {}
