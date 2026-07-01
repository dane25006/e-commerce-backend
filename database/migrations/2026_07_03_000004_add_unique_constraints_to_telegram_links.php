<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('telegram_links', function (Blueprint $table) {
            // Remove duplicate rows first (keep the latest per user)
            $duplicates = \Illuminate\Support\Facades\DB::table('telegram_links')
                ->select('user_id', \Illuminate\Support\Facades\DB::raw('MAX(id) as max_id'))
                ->groupBy('user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicates as $dup) {
                \Illuminate\Support\Facades\DB::table('telegram_links')
                    ->where('user_id', $dup->user_id)
                    ->where('id', '!=', $dup->max_id)
                    ->delete();
            }

            // Add unique constraint on user_id
            try {
                $table->unique('user_id', 'uk_telegram_links_user');
            } catch (\Exception $e) {
                // Index may already exist
            }

            // Add unique constraint on telegram_chat_id (MySQL allows multiple NULLs)
            try {
                $table->unique('telegram_chat_id', 'uk_telegram_links_chat');
            } catch (\Exception $e) {
                // Index may already exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('telegram_links', function (Blueprint $table) {
            $table->dropUnique('uk_telegram_links_user');
            $table->dropUnique('uk_telegram_links_chat');
        });
    }
};
