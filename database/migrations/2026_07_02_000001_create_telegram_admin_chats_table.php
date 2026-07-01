<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_admin_chats', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 50)->unique();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->enum('role', ['super_admin', 'admin', 'moderator'])->default('admin');
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_orders')->default(true);
            $table->boolean('notify_payments')->default(true);
            $table->boolean('notify_stock')->default(true);
            $table->boolean('notify_reports')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_admin_chats');
    }
};
