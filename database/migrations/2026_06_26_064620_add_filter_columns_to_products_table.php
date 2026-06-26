<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('stock');
            $table->string('brand')->nullable()->after('gender');
            $table->string('type')->nullable()->after('brand');
            $table->string('department')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gender', 'brand', 'type', 'department']);
        });
    }
};
