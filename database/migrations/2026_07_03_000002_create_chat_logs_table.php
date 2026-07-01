<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id', 50)->nullable();
            $table->string('type', 30)->default('text');
            $table->string('direction', 10)->default('incoming');
            $table->text('message')->nullable();
            $table->text('response')->nullable();
            $table->string('intent', 50)->nullable();
            $table->json('metadata')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_logs');
    }
};
