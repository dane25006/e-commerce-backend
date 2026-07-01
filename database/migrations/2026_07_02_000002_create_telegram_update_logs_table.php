<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_update_logs', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('update_id')->nullable()->unique();
            $table->string('chat_id', 50)->nullable();
            $table->string('type', 30)->default('unknown');
            $table->string('intent', 50)->nullable();
            $table->json('raw_payload')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->string('job_uuid', 36)->nullable();
            $table->text('error_message')->nullable();
            $table->integer('processing_time_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('type');
            $table->index('is_processed');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_update_logs');
    }
};
