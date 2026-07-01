<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type', 30)->default('string');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Default settings
        $now = now();
        \Illuminate\Support\Facades\DB::table('bot_settings')->insert([
            ['key' => 'ai_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable AI Chat feature', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'image_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Enable Image Generation', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ai_model', 'value' => 'deepseek-chat', 'type' => 'string', 'description' => 'AI model to use', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'ai_temperature', 'value' => '0.7', 'type' => 'float', 'description' => 'AI response creativity', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'image_style', 'value' => 'realistic', 'type' => 'string', 'description' => 'Default image style', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'broadcast_limit', 'value' => '100', 'type' => 'integer', 'description' => 'Max broadcast per hour', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'welcome_message', 'value' => 'Welcome! Use the menu below to get started.', 'type' => 'text', 'description' => 'Welcome message for new users', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_settings');
    }
};
