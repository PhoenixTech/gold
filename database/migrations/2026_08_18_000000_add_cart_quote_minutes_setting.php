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
        if (! Setting::where('key', 'cart_quote_minutes')->exists()) {
            Setting::query()->create([
                'title' => __('Cart quote duration (minutes)'),
                'key' => 'cart_quote_minutes',
                'section' => 'General',
                'type' => 'NUMBER',
                'ltr' => true,
                'value' => '30',
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
        Setting::where('key', 'cart_quote_minutes')->delete();
    }
};
