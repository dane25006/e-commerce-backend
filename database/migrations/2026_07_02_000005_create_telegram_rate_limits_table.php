<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 50);
            $table->string('endpoint', 100)->default('sendMessage');
            $table->integer('request_count')->default(1);
            $table->timestamp('window_start');
            $table->timestamps();

            $table->unique(['chat_id', 'endpoint', 'window_start'], 'uk_rate_limit');
            $table->index('window_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_rate_limits');
    }
};
