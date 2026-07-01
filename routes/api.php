<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PromotionApiController;

// ── PUBLIC ────────────────────────────────────────────────────────────

// Auth
Route::post('register', [AuthApiController::class, 'register']);
Route::post('login',    [AuthApiController::class, 'login']);

// Google OAuth
Route::get('auth/google/redirect', [App\Http\Controllers\Api\GoogleSocialiteController::class, 'redirect']);
Route::get('auth/google/callback', [App\Http\Controllers\Api\GoogleSocialiteController::class, 'callback']);

// Serve storage files through API (CORS-enabled)
Route::get('storage/{path}', [App\Http\Controllers\Api\StorageController::class, '__invoke'])
    ->where('path', '.*');

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

// Telegram webhook (no CSRF, called by Telegram)
Route::post('telegram/webhook', [App\Http\Controllers\Api\TelegramController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// ── PROTECTED — auth:sanctum ───────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('logout',   [AuthApiController::class, 'logout']);
    Route::get ('profile',  [AuthApiController::class, 'profile']);
    Route::put ('profile',  [AuthApiController::class, 'updateProfile']);
    Route::put ('password', [AuthApiController::class, 'changePassword']);

    // Merge guest data on login
    Route::post('cart/merge',          [CartController::class, 'merge']);
    Route::post('wishlist/merge',      [WishlistController::class, 'merge']);

    // Checkout & Orders
    Route::post('checkout',                  [OrderApiController::class, 'checkout']);
    Route::get ('orders',                    [OrderApiController::class, 'index']);
    Route::get ('orders/{order}',            [OrderApiController::class, 'show']);
    Route::put ('orders/{order}/cancel',     [OrderApiController::class, 'cancel']);

    // Reviews (write)
    Route::post  ('products/{product}/reviews',            [ReviewController::class, 'store']);
    Route::put   ('products/{product}/reviews/{review}',   [ReviewController::class, 'update']);
    Route::delete('products/{product}/reviews/{review}',   [ReviewController::class, 'destroy']);

    // Telegram
    Route::get('telegram/status',                [App\Http\Controllers\Api\TelegramController::class, 'status']);
    Route::get('telegram/connect',               [App\Http\Controllers\Api\TelegramController::class, 'generateCode']);
    Route::post('telegram/generate',             [App\Http\Controllers\Api\TelegramController::class, 'generateCode']);
    Route::post('telegram/toggle-notifications', [App\Http\Controllers\Api\TelegramController::class, 'toggleNotifications']);
    Route::post('telegram/unlink',               [App\Http\Controllers\Api\TelegramController::class, 'unlink']);
    Route::delete('telegram/destroy',            [App\Http\Controllers\Api\TelegramController::class, 'unlink']);
    Route::post('telegram/send-test',            [App\Http\Controllers\Api\TelegramController::class, 'sendTest']);
});
