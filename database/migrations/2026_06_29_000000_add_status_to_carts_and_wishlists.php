<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('quantity');
            $table->index('status');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('product_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
