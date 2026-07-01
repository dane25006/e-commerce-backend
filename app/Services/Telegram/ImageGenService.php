<?php

namespace App\Services\Telegram;

use App\Models\BotSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageGenService
{
    protected string $apiKey;
    protected string $endpoint;
    protected string $style;

    public function __construct()
    {
        $this->apiKey = config('services.image.api_key', env('IMAGE_API_KEY', env('AI_API_KEY', '')));
        $this->endpoint = config('services.image.endpoint', 'https://api.deepseek.com/v1/images/generations');
        $this->style = BotSetting::get('image_style', 'realistic');
    }

    public function generate(string $prompt): ?array
    {
        if (! BotSetting::isEnabled('image_enabled')) {
            return [
                'text' => "🖼 Image generation is currently disabled by the admin.\nPlease try again later.",
                'file' => null,
            ];
        }

        if (empty($this->apiKey)) {
            Log::warning('Image Gen: No API key configured');
            return [
                'text' => "⚠️ Image generation service is not configured.\nPlease set IMAGE_API_KEY in .env",
                'file' => null,
            ];
        }

        try {
            $enhancedPrompt = $this->buildPrompt($prompt);

            $response = Http::timeout(60)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type'  => 'application/json',
                ])
                ->post($this->endpoint, [
                    'prompt'          => $enhancedPrompt,
                    'n'               => 1,
                    'size'            => '1024x1024',
                    'response_format' => 'url',
                ]);

            if ($response->failed()) {
                Log::error('Image Gen API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                if ($response->status() === 429) {
                    return ['text' => "⏳ Too many requests. Please wait a moment.", 'file' => null];
                }

                return ['text' => "⚠️ Image generation failed. Try a different description.", 'file' => null];
            }

            $data = $response->json();
            $imageUrl = $data['data'][0]['url'] ?? null;

            if (! $imageUrl) {
                return ['text' => "⚠️ No image was generated. Try again.", 'file' => null];
            }

            // Download the image
            $imageContent = Http::timeout(30)->get($imageUrl)->body();
            $filename = 'ai_images/' . md5($prompt . now()) . '.png';
            Storage::disk('public')->put($filename, $imageContent);

            return [
                'text'   => "🎨 <b>Generated:</b> {$prompt}",
                'file'   => Storage::url($filename),
                'style'  => $this->style,
            ];

        } catch (\Exception $e) {
            Log::error('Image Gen exception', ['error' => $e->getMessage()]);
            return ['text' => "⚠️ Connection error. Please try again.", 'file' => null];
        }
    }

    protected function buildPrompt(string $userPrompt): string
    {
        $styleGuide = match ($this->style) {
            'realistic' => 'photorealistic, high detail, 8K, professional lighting',
            'artistic'  => 'digital art, vibrant colors, artistic style, painterly',
            'minimalist' => 'minimalist, clean, simple, elegant, white background',
            default     => 'high quality, detailed',
        };

        return "{$userPrompt}, {$styleGuide}";
    }
}
