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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoices') && DB::getDriverName() !== 'sqlite') {
            Invoice::query()
                ->where('status', Invoice::OUT_FOR_DELIVERY)
                ->update(['status' => Invoice::PROCESSING]);

            $statuses = ['PENDING', 'AWAITING_PAYMENT', 'CANCELED', 'FAILED', 'PAID', 'PROCESSING', 'COMPLETED'];
            $list = implode("','", $statuses);
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('{$list}') NULL DEFAULT 'PENDING'");
        }
    }
};
