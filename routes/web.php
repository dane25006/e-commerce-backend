<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PromotionController;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    Route::middleware('admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', fn() => redirect()->route('admin.dashboard'));
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('products', ProductController::class)->except(['show']);
        Route::get('orders',        [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::resource('promotions', PromotionController::class)->except(['show']);

        // Telegram admin panel
        Route::prefix('telegram')->name('telegram.')->group(function () {
            Route::get('/',                         [App\Http\Controllers\Admin\TelegramController::class, 'index'])->name('dashboard');
            Route::get('logs',                      [App\Http\Controllers\Admin\TelegramController::class, 'logs'])->name('logs');
            Route::get('logs/{id}',                 [App\Http\Controllers\Admin\TelegramController::class, 'logDetail'])->name('log.detail');
            Route::get('failed',                    [App\Http\Controllers\Admin\TelegramController::class, 'failed'])->name('failed');
            Route::post('failed/{id}/retry',        [App\Http\Controllers\Admin\TelegramController::class, 'retryFailed'])->name('failed.retry');
            Route::post('failed/retry-all',         [App\Http\Controllers\Admin\TelegramController::class, 'retryAllFailed'])->name('failed.retry-all');
            Route::get('settings',                  [App\Http\Controllers\Admin\TelegramController::class, 'settings'])->name('settings');
            Route::post('settings',                 [App\Http\Controllers\Admin\TelegramController::class, 'updateSettings'])->name('settings.update');
            Route::post('settings/test',            [App\Http\Controllers\Admin\TelegramController::class, 'sendTest'])->name('settings.test');
            Route::get('chats',                     [App\Http\Controllers\Admin\TelegramController::class, 'chats'])->name('chats');
            Route::post('chats',                    [App\Http\Controllers\Admin\TelegramController::class, 'storeChat'])->name('chats.store');
            Route::delete('chats/{id}',             [App\Http\Controllers\Admin\TelegramController::class, 'destroyChat'])->name('chats.destroy');
            Route::post('chats/{id}/toggle',        [App\Http\Controllers\Admin\TelegramController::class, 'toggleChat'])->name('chats.toggle');
        });
    });
});
Route::get('/', fn() => redirect()->route('admin.login'));
