<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('invoices') && DB::getDriverName() !== 'sqlite') {
            $statuses = implode("','", Invoice::$invoiceStatus);
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('{$statuses}') NULL DEFAULT 'PENDING'");
        }

        if (Schema::hasTable('invoices') && Schema::hasTable('payments')) {
            Invoice::query()
                ->where('status', Invoice::PENDING)
                ->whereHas('payments', function ($query) {
                    $query->where('type', 'CARD');
                })
                ->update(['status' => Invoice::AWAITING_PAYMENT]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasTable('payments')) {
            Invoice::query()
                ->where('status', Invoice::AWAITING_PAYMENT)
                ->update(['status' => Invoice::PENDING]);
        }

        if (Schema::hasTable('invoices') && DB::getDriverName() !== 'sqlite') {
            $statuses = ['PENDING', 'CANCELED', 'FAILED', 'PAID', 'PROCESSING', 'COMPLETED'];
            $list = implode("','", $statuses);
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('{$list}') NULL DEFAULT 'PENDING'");
        }
    }
};
