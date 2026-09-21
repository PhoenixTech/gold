<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('delivery_type', 32)->default('address')->after('status');
            $table->boolean('is_third_party')->default(false)->after('delivery_type');
            $table->string('recipient_name')->nullable()->after('is_third_party');
            $table->string('recipient_mobile', 20)->nullable()->after('recipient_name');
            $table->string('recipient_national_id', 20)->nullable()->after('recipient_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_type',
                'is_third_party',
                'recipient_name',
                'recipient_mobile',
                'recipient_national_id',
            ]);
        });
    }
};
