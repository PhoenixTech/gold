<?php

use App\Models\Transport;
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
        Schema::table('transports', function (Blueprint $table) {
            $table->boolean('requires_delivery_code')->default(false)->after('is_default');
        });

        Transport::query()->each(function (Transport $transport): void {
            $title = mb_strtolower((string) $transport->title);
            if (
                str_contains($title, 'پیک')
                || str_contains($title, 'motor')
                || str_contains($title, 'موتور')
            ) {
                $transport->requires_delivery_code = true;
                $transport->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transports', function (Blueprint $table) {
            $table->dropColumn('requires_delivery_code');
        });
    }
};
