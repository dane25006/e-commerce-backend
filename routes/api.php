<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes — E-Commerce Backend
|--------------------------------------------------------------------------
| Public    → no token needed
| Protected → Authorization: Bearer {token}
*/

// ═══════════════════════════════════════════════════════════════
// PUBLIC — no token needed
// ═══════════════════════════════════════════════════════════════

// Auth
Route::post('register', [AuthApiController::class, 'register']);
Route::post('login',    [AuthApiController::class, 'login']);

// Products & Categories
Route::get('categories',             [ProductApiController::class, 'categories']);
Route::get('products',               [ProductApiController::class, 'index']);
Route::get('products/{product}',     [ProductApiController::class, 'show']);

// Reviews — reading is public
Route::get('products/{product}/reviews', [ReviewController::class, 'index']);

// ═══════════════════════════════════════════════════════════════
// PROTECTED — requires: Authorization: Bearer {token}
// ═══════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────────────────────
    Route::post('logout',   [AuthApiController::class, 'logout']);
    Route::get ('profile',  [AuthApiController::class, 'profile']);
    Route::put ('profile',  [AuthApiController::class, 'updateProfile']);
    Route::put ('password', [AuthApiController::class, 'changePassword']);

    // ── Wishlist ──────────────────────────────────────────────
    Route::get   ('wishlist',              [WishlistController::class, 'index']);
    Route::post  ('wishlist',              [WishlistController::class, 'store']);
    Route::post  ('wishlist/toggle',       [WishlistController::class, 'toggle']);   // ← NEW: heart button
    Route::delete('wishlist/{wishlist}',   [WishlistController::class, 'destroy']);

    // ── Cart ──────────────────────────────────────────────────
    Route::get   ('cart',          [CartController::class, 'index']);
    Route::post  ('cart',          [CartController::class, 'store']);
    Route::put   ('cart/{cart}',   [CartController::class, 'update']);
    Route::delete('cart/{cart}',   [CartController::class, 'destroy']);
    Route::delete('cart',          [CartController::class, 'clear']);               // ← NEW: clear entire cart

    // ── Checkout & Orders ─────────────────────────────────────
    Route::post('checkout',                  [OrderApiController::class, 'checkout']);
    Route::get ('orders',                    [OrderApiController::class, 'index']);
    Route::get ('orders/{order}',            [OrderApiController::class, 'show']);
    Route::put ('orders/{order}/cancel',     [OrderApiController::class, 'cancel']); // ← NEW: cancel pending order

    // ── Reviews (write) ───────────────────────────────────────
    Route::post  ('products/{product}/reviews',            [ReviewController::class, 'store']);
    Route::put   ('products/{product}/reviews/{review}',   [ReviewController::class, 'update']);   // ← NEW: edit review
    Route::delete('products/{product}/reviews/{review}',   [ReviewController::class, 'destroy']);  // ← NEW: delete review
});

