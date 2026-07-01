<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\TelegramRateLimit;

class TelegramRateLimit
{
    public function handle(Request $request, Closure $next)
    {
        $payload = $request->all();
        $chatId = $payload['message']['chat']['id']
            ?? $payload['callback_query']['message']['chat']['id']
            ?? null;

        if ($chatId) {
            $chatId = (string) $chatId;
            $maxPerMin = config('telegram.rate_limit_per_minute', 30);

            $count = TelegramRateLimit::countInWindow($chatId);

            if ($count >= $maxPerMin) {
                Log::warning("Telegram: Rate limit hit for chat {$chatId} ({$count}/{$maxPerMin})");
                return response('Too Many Requests', 429);
            }

            try {
                TelegramRateLimit::incrementCount($chatId);
            } catch (\Exception $e) {
                // Non-critical — allow request through
            }
        }

        return $next($request);
    }
}
