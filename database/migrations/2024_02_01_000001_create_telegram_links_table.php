<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_id')->nullable()->unique();
            $table->string('username')->nullable();
            $table->bigInteger('chat_id')->nullable();
            $table->string('link_token', 64)->nullable()->unique()->index();
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_links');
    }
};
