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
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'national_code')) {
                $table->string('national_code', 10)->nullable()->after('dob');
            }
            if (! Schema::hasColumn('customers', 'emergency_phone')) {
                $table->string('emergency_phone', 20)->nullable()->after('national_code');
            }
            if (! Schema::hasColumn('customers', 'bank_card')) {
                $table->string('bank_card', 25)->nullable()->after('emergency_phone');
            }
            if (! Schema::hasColumn('customers', 'bank_sheba')) {
                $table->string('bank_sheba', 35)->nullable()->after('bank_card');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $columns = ['national_code', 'emergency_phone', 'bank_card', 'bank_sheba'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
