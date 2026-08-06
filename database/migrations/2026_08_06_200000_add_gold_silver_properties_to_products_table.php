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
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 10, 3)->nullable()->default(0)->after('buy_price')->comment('Weight in grams');
            }
            if (!Schema::hasColumn('products', 'labor_charge_1')) {
                $table->decimal('labor_charge_1', 15, 2)->nullable()->default(0)->after('wage');
            }
            if (!Schema::hasColumn('products', 'labor_charge_2')) {
                $table->decimal('labor_charge_2', 15, 2)->nullable()->default(0)->after('labor_charge_1');
            }
            if (!Schema::hasColumn('products', 'labor_charge_3')) {
                $table->decimal('labor_charge_3', 15, 2)->nullable()->default(0)->after('labor_charge_2');
            }
            if (!Schema::hasColumn('products', 'profit')) {
                $table->decimal('profit', 8, 2)->nullable()->default(0)->after('labor_charge_3');
            }
            if (!Schema::hasColumn('products', 'tax')) {
                $table->decimal('tax', 8, 2)->nullable()->default(0)->after('profit');
            }
            if (!Schema::hasColumn('products', 'min_stock_level')) {
                $table->integer('min_stock_level')->nullable()->default(0)->after('stock_quantity');
            }
            if (!Schema::hasColumn('products', 'target_group')) {
                $table->string('target_group', 32)->nullable()->default('unisex')->after('category_id');
            }
            if (!Schema::hasColumn('products', 'metal_type')) {
                $table->string('metal_type', 32)->nullable()->default('gold')->after('target_group');
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
                'weight',
                'labor_charge_1',
                'labor_charge_2',
                'labor_charge_3',
                'profit',
                'tax',
                'min_stock_level',
                'target_group',
                'metal_type',
            ]);
        });
    }
};
