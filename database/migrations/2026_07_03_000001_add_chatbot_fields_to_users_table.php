<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_id')) {
                $table->string('telegram_id', 50)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username', 255)->nullable()->after('telegram_id');
            }
            if (! Schema::hasColumn('users', 'language')) {
                $table->string('language', 10)->default('en')->after('telegram_username');
            }
            if (! Schema::hasColumn('users', 'is_bot_admin')) {
                $table->boolean('is_bot_admin')->default(false)->after('language');
            }
            if (! Schema::hasColumn('users', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable()->after('is_bot_admin');
            }
            if (! Schema::hasColumn('users', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('last_activity_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'language', 'is_bot_admin', 'last_activity_at', 'registered_at']);
        });
    }
};
