<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quantities', function (Blueprint $table) {
            $table->string('status', 30)->default('available')->after('code')->index();
        });

        DB::table('quantities')->where('count', '<=', 0)->update(['status' => 'sold']);
        DB::table('quantities')->where('count', '>', 0)->update(['status' => 'available']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quantities', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
