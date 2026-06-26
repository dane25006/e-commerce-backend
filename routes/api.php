<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PromotionApiController;

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

// Google OAuth
Route::get('auth/google/redirect', [App\Http\Controllers\Api\GoogleSocialiteController::class, 'redirect']);
Route::get('auth/google/callback', [App\Http\Controllers\Api\GoogleSocialiteController::class, 'callback']);

// Products & Categories
Route::get('filters',                [ProductApiController::class, 'filters']);
Route::get('categories',             [ProductApiController::class, 'categories']);
Route::get('products',               [ProductApiController::class, 'index']);
Route::get('products/{product}',     [ProductApiController::class, 'show']);

// Reviews — reading is public
Route::get('products/{product}/reviews', [ReviewController::class, 'index']);

// Promotions
Route::get('promotions',                [PromotionApiController::class, 'index']);
Route::get('promotions/{promotion}',    [PromotionApiController::class, 'show']);
Route::post('promotions/validate',      [PromotionApiController::class, 'validate']);

// Cart & Wishlist — public (guest_token or auth)
Route::get   ('cart',                [CartController::class, 'index']);
Route::post  ('cart',                [CartController::class, 'store']);
Route::put   ('cart/{cart}',         [CartController::class, 'update']);
Route::delete('cart/{cart}',         [CartController::class, 'destroy']);
Route::delete('cart',                [CartController::class, 'clear']);

Route::get   ('wishlist',            [WishlistController::class, 'index']);
Route::post  ('wishlist',            [WishlistController::class, 'store']);
Route::post  ('wishlist/toggle',     [WishlistController::class, 'toggle']);
Route::delete('wishlist/{wishlist}', [WishlistController::class, 'destroy']);

// ═══════════════════════════════════════════════════════════════
// PROTECTED — requires: Authorization: Bearer {token}
// ═══════════════════════════════════════════════════════════════
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────────────────────
    Route::post('logout',   [AuthApiController::class, 'logout']);
    Route::get ('profile',  [AuthApiController::class, 'profile']);
    Route::put ('profile',  [AuthApiController::class, 'updateProfile']);
    Route::put ('password', [AuthApiController::class, 'changePassword']);

    // ── Merge guest data on login ─────────────────────────────
    Route::post('cart/merge',          [CartController::class, 'merge']);
    Route::post('wishlist/merge',      [WishlistController::class, 'merge']);

    // ── Checkout & Orders ─────────────────────────────────────
    Route::post('checkout',                  [OrderApiController::class, 'checkout']);
    Route::get ('orders',                    [OrderApiController::class, 'index']);
    Route::get ('orders/{order}',            [OrderApiController::class, 'show']);
    Route::put ('orders/{order}/cancel',     [OrderApiController::class, 'cancel']);

    // ── Reviews (write) ───────────────────────────────────────
    Route::post  ('products/{product}/reviews',            [ReviewController::class, 'store']);
    Route::put   ('products/{product}/reviews/{review}',   [ReviewController::class, 'update']);
    Route::delete('products/{product}/reviews/{review}',   [ReviewController::class, 'destroy']);
});

