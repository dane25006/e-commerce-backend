<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withEvents(discover: [
        __DIR__ . '/../app/Listeners',
    ])
    ->withProviders([
        \App\Providers\TelegramServiceProvider::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->redirectGuestsTo(fn () => null);// ← fix: return 401 JSON instead of redirect to route('login')

        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);

        $middleware->alias([
            'admin'                => \App\Http\Middleware\IsAdmin::class,
            'telegram.webhook-auth'=> \App\Http\Middleware\TelegramWebhookAuth::class,
            'telegram.rate-limit'  => \App\Http\Middleware\TelegramRateLimit::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();