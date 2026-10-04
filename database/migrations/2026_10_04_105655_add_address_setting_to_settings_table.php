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
        if (Setting::where('key', 'address')->exists()) {
            return;
        }

        $setting = new Setting;
        $setting->title = 'Address';
        $setting->section = 'General';
        $setting->key = 'address';
        $setting->value = '';
        $setting->type = 'LONGTEXT';
        $setting->ltr = false;
        $setting->active = true;
        $setting->is_basic = true;
        $setting->size = 12;
        $setting->save();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Setting::where('key', 'address')->delete();
    }
};
