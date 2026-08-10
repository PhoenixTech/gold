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
            $table->decimal('weight', 10, 3)->nullable()->after('price');
            $table->string('code')->nullable()->after('weight');
        });

        $quantities = DB::table('quantities')->whereNotNull('data')->get(['id', 'data', 'count']);

        foreach ($quantities as $quantity) {
            $data = json_decode($quantity->data, true);
            $weight = is_array($data) ? ($data['weight'] ?? null) : null;

            $updates = [];
            if ($weight !== null && $weight !== '') {
                $updates['weight'] = $weight;
            }
            if ((int) $quantity->count === 0 && $weight !== null && $weight !== '') {
                $updates['count'] = 1;
            }

            if ($updates !== []) {
                DB::table('quantities')->where('id', $quantity->id)->update($updates);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quantities', function (Blueprint $table) {
            $table->dropColumn(['weight', 'code']);
        });
    }
};
