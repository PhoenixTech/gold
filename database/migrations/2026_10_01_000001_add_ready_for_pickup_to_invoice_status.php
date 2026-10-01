<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && DB::getDriverName() !== 'sqlite') {
            $statuses = implode("','", Invoice::$invoiceStatus);
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('{$statuses}') NULL DEFAULT 'PENDING'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        Invoice::query()
            ->where('status', Invoice::READY_FOR_PICKUP)
            ->update(['status' => Invoice::PROCESSING]);

        if (DB::getDriverName() !== 'sqlite') {
            $statuses = array_diff(Invoice::$invoiceStatus, [Invoice::READY_FOR_PICKUP]);
            $list = implode("','", $statuses);
            DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('{$list}') NULL DEFAULT 'PENDING'");
        }
    }
};
