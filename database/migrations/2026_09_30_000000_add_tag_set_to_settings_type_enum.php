<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The `type` column is a native ENUM on MySQL, so an already migrated
     * database has to be widened before the new TAG_SET setting can be stored.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `settings` MODIFY `type` ENUM('.$this->typeList().') NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE `settings` MODIFY `type` ENUM('.$this->typeList(false).') NOT NULL');
    }

    /**
     * @return string comma separated, quoted enum members
     */
    private function typeList(bool $withTagSet = true): string
    {
        $types = array_values(array_filter(
            Setting::$settingTypes,
            fn (string $type): bool => $withTagSet || $type !== 'TAG_SET'
        ));

        return implode(',', array_map(fn (string $type): string => "'".$type."'", $types));
    }
};
