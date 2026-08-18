<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Setting::where('key', 'offline_payment_hours')->exists()) {
            Setting::query()->create([
                'title' => __('Offline payment deadline (hours)'),
                'key' => 'offline_payment_hours',
                'section' => 'General',
                'type' => 'NUMBER',
                'ltr' => true,
                'value' => '3',
                'size' => '4',
                'is_basic' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'offline_payment_hours')->delete();
    }
};
