<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_message_logs', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 50);
            $table->enum('direction', ['outgoing', 'incoming'])->default('outgoing');
            $table->string('type', 30)->default('text');
            $table->string('text_preview', 255)->nullable();
            $table->longText('full_text')->nullable();
            $table->json('buttons')->nullable();
            $table->bigInteger('telegram_message_id')->nullable();
            $table->boolean('is_delivered')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index('chat_id');
            $table->index('direction');
            $table->index('is_delivered');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_message_logs');
    }
};
