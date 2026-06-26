<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->after('user_id');
            $table->index('guest_token');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('guest_token', 64)->nullable()->after('user_id');
            $table->index('guest_token');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn('guest_token');
        });
    }
};
