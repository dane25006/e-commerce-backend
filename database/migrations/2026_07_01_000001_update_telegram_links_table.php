<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_links', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (! Schema::hasColumn('telegram_links', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('user_id')->index();
            }
            if (! Schema::hasColumn('telegram_links', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_chat_id');
            }
            if (! Schema::hasColumn('telegram_links', 'verification_code')) {
                $table->string('verification_code', 64)->nullable()->after('notifications_enabled')->index();
            }
            if (! Schema::hasColumn('telegram_links', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_code');
            }

            // Migrate old data: copy chat_id → telegram_chat_id
            if (Schema::hasColumn('telegram_links', 'chat_id') && Schema::hasColumn('telegram_links', 'telegram_chat_id')) {
                DB::table('telegram_links')
                    ->whereNotNull('chat_id')
                    ->whereNull('telegram_chat_id')
                    ->update(['telegram_chat_id' => DB::raw('CAST(chat_id AS CHAR)')]);
            }

            // Migrate old data: copy username → telegram_username
            if (Schema::hasColumn('telegram_links', 'username') && Schema::hasColumn('telegram_links', 'telegram_username')) {
                DB::table('telegram_links')
                    ->whereNotNull('username')
                    ->whereNull('telegram_username')
                    ->update(['telegram_username' => DB::raw('username')]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_links', function (Blueprint $table) {
            $columns = ['telegram_chat_id', 'telegram_username', 'verification_code', 'verified_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('telegram_links', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
