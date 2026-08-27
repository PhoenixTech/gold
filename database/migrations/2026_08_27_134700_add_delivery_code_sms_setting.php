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
        if (! Setting::query()->where('key', 'delivery_code')->exists()) {
            Setting::query()->create([
                'title' => __('Delivery confirmation'),
                'key' => 'delivery_code',
                'section' => 'SMS',
                'type' => 'LONGTEXT',
                'value' => 'delivery_code',
                'size' => '12',
                'is_basic' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::query()->where('key', 'delivery_code')->delete();
    }
};
