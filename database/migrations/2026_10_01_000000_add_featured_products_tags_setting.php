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
        if (! Setting::where('key', 'index_FeaturedProducts_tags')->exists()) {
            Setting::query()->create([
                'title' => __('Home page featured product tags'),
                'key' => 'index_FeaturedProducts_tags',
                'section' => 'Homepage',
                'type' => 'TAG_SET',
                'ltr' => true,
                'value' => '[]',
                'raw' => '[]',
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
        Setting::where('key', 'index_FeaturedProducts_tags')->delete();
    }
};
