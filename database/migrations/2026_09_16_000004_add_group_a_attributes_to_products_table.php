<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'plating_colors')) {
                $table->json('plating_colors')->nullable()->after('metal_type');
            }
            if (! Schema::hasColumn('products', 'stones')) {
                $table->json('stones')->nullable()->after('plating_colors');
            }
            if (! Schema::hasColumn('products', 'accessories')) {
                $table->json('accessories')->nullable()->after('stones');
            }
            if (! Schema::hasColumn('products', 'occasions')) {
                $table->json('occasions')->nullable()->after('accessories');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'plating_colors',
                'stones',
                'accessories',
                'occasions',
            ]);
        });
    }
};
