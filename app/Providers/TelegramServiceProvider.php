<?php

namespace App\Providers;

use App\Services\Telegram\TelegramAdminService;
use App\Services\Telegram\TelegramBotService;
use App\Services\Telegram\TelegramLogService;
use App\Services\Telegram\TelegramSecurityService;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TelegramBotService::class, function () {
            return new TelegramBotService();
        });

        $this->app->singleton(TelegramLogService::class, function () {
            return new TelegramLogService();
        });

        $this->app->singleton(TelegramSecurityService::class, function () {
            return new TelegramSecurityService();
        });

        $this->app->singleton(TelegramAdminService::class, function ($app) {
            return new TelegramAdminService(
                $app->make(TelegramBotService::class),
                $app->make(TelegramLogService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
