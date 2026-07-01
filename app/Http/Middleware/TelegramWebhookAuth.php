<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookAuth
{
    public function handle(Request $request, Closure $next)
    {
        $expected = config('telegram.webhook_secret');

        if (! $expected) {
            return $next($request);
        }

        $queryToken = $request->query('token');
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if ($queryToken && hash_equals($expected, $queryToken)) {
            return $next($request);
        }

        if ($headerToken && hash_equals($expected, $headerToken)) {
            return $next($request);
        }

        Log::warning('Telegram: Webhook auth failed', [
            'ip'       => $request->ip(),
            'query'    => $queryToken ? 'present' : 'missing',
            'header'   => $headerToken ? 'present' : 'missing',
        ]);

        return response('Unauthorized', 403);
    }
}
