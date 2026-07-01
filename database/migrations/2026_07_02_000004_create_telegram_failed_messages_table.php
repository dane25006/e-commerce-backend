<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_failed_messages', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 50)->nullable();
            $table->string('job_class');
            $table->json('payload')->nullable();
            $table->text('exception_message')->nullable();
            $table->text('exception_trace')->nullable();
            $table->integer('retry_count')->default(0);
            $table->integer('max_retries')->default(3);
            $table->timestamp('last_attempt_at')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('is_resolved');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_failed_messages');
    }
};
