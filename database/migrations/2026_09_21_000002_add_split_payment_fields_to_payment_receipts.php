<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('amount')->nullable()->after('size');
            $table->string('payment_date')->nullable()->after('amount');
            $table->string('payment_time')->nullable()->after('payment_date');
            $table->string('tracking_number')->nullable()->after('payment_time');
            $table->foreignId('bank_account_id')->nullable()->after('tracking_number')->constrained('bank_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_receipts', function (Blueprint $table) {
            $table->dropForeign(['bank_account_id']);
            $table->dropColumn([
                'amount',
                'payment_date',
                'payment_time',
                'tracking_number',
                'bank_account_id',
            ]);
        });
    }
};
