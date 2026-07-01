<?php

return [
    'bot_token'      => env('TELEGRAM_BOT_TOKEN'),
    'bot_username'   => env('TELEGRAM_BOT_USERNAME'),
    'admin_chat_id'  => env('TELEGRAM_ADMIN_CHAT_ID'),
    'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
    'webhook_url'    => env('TELEGRAM_WEBHOOK_URL', env('APP_URL') . '/api/telegram/webhook'),
    'queue_connection' => env('TELEGRAM_QUEUE_CONNECTION', 'database'),
    'queue_name'       => env('TELEGRAM_QUEUE_NAME', 'telegram'),
    'rate_limit_per_minute' => (int) env('TELEGRAM_RATE_LIMIT', 30),
    'max_retries'       => (int) env('TELEGRAM_MAX_RETRIES', 3),
    'retry_delay_base'  => (int) env('TELEGRAM_RETRY_DELAY', 5),
    'log_incoming'      => (bool) env('TELEGRAM_LOG_INCOMING', true),
    'log_outgoing'      => (bool) env('TELEGRAM_LOG_OUTGOING', true),
    'log_failures'      => (bool) env('TELEGRAM_LOG_FAILURES', true),
    'log_retention_days'=> (int) env('TELEGRAM_LOG_RETENTION', 30),
];
