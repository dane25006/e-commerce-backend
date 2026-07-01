<?php

namespace App\Services\Telegram;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiChatService
{
    protected string $endpoint;
    protected string $apiKey;
    protected string $model;
    protected float $temperature;

    public function __construct()
    {
        $this->apiKey = config('services.ai.api_key', env('AI_API_KEY', ''));
        $this->endpoint = config('services.ai.endpoint', 'https://api.deepseek.com/v1/chat/completions');
        $this->model = BotSetting::get('ai_model', 'deepseek-chat');
        $this->temperature = (float) BotSetting::get('ai_temperature', 0.7);
    }

    public function ask(string $message, array $history = []): ?array
    {
        if (! BotSetting::isEnabled('ai_enabled')) {
            return [
                'text' => "🧠 AI Chat is currently disabled by the admin.\nPlease try again later.",
                'tokens' => 0,
            ];
        }

        if (empty($this->apiKey)) {
            Log::warning('AI Chat: No API key configured');
            return [
                'text' => "⚠️ AI service is not configured.\nPlease set AI_API_KEY in .env",
                'tokens' => 0,
            ];
        }

        $messages = $this->buildMessages($message, $history);

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->endpoint, [
                    'model'       => $this->model,
                    'messages'    => $messages,
                    'temperature' => $this->temperature,
                    'max_tokens'  => 1024,
                ]);

            if ($response->failed()) {
                Log::error('AI Chat API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                if ($response->status() === 429) {
                    return ['text' => "⏳ Too many requests. Please wait a moment and try again.", 'tokens' => 0];
                }

                return ['text' => "⚠️ AI service temporarily unavailable. Please try again later.", 'tokens' => 0];
            }

            $data = $response->json();
            $text = $data['choices'][0]['message']['content'] ?? 'No response';
            $tokens = $data['usage']['total_tokens'] ?? 0;

            return [
                'text'   => $this->formatResponse($text),
                'tokens' => $tokens,
            ];

        } catch (\Exception $e) {
            Log::error('AI Chat exception', ['error' => $e->getMessage()]);
            return ['text' => "⚠️ Connection error. Please try again.", 'tokens' => 0];
        }
    }

    protected function buildMessages(string $message, array $history = []): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful shopping assistant for Scentique, a luxury perfume e-commerce store. '
                    . 'You help customers with product recommendations, order questions, and general inquiries. '
                    . 'Keep responses concise, friendly, and under 200 words. '
                    . 'Use emojis occasionally to keep the conversation engaging.',
            ],
        ];

        foreach (array_slice($history, -10) as $h) {
            $messages[] = ['role' => 'user', 'content' => $h['message'] ?? ''];
            if (! empty($h['response'])) {
                $messages[] = ['role' => 'assistant', 'content' => $h['response']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $message];

        return $messages;
    }

    protected function formatResponse(string $text): string
    {
        // Ensure proper HTML escaping for Telegram
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Convert markdown-like bold to HTML
        $text = preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<i>$1</i>', $text);

        return $text;
    }
}
