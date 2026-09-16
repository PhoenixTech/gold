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
        if (!Schema::hasColumn('categories', 'code')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('code', 10)->nullable()->after('slug')->index();
            });
        }

        // Populate standard codes for existing categories if recognizable
        $mappings = [
            'Gr' => ['گردنبند', 'آویز', 'necklace', 'pendant'],
            'E' => ['گوشواره', 'earring'],
            'A' => ['انگشتر', 'حلقه', 'ring'],
            'L' => ['النگو', 'bangle'],
            'D' => ['دستبند', 'bracelet'],
            'P' => ['پابند', 'خلخال', 'anklet'],
            'Z' => ['زنجیر', 'chain'],
            'Ac' => ['اکسسوری', 'accessory'],
            'Pr' => ['پیرسینگ', 'piercing'],
            'Set' => ['نیم ست', 'نیم‌ست', 'ست', 'set'],
            'Sh' => ['شمش', 'ingot', 'bar'],
        ];

        $categories = DB::table('categories')->get();
        foreach ($categories as $category) {
            $rawName = (string) $category->name;
            // Decode spatie translatable json if applicable
            $decoded = json_decode($rawName, true);
            $searchableText = is_array($decoded) ? implode(' ', $decoded) : $rawName;
            $searchableText .= ' ' . ($category->slug ?? '');

            foreach ($mappings as $code => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_stripos($searchableText, $keyword) !== false) {
                        DB::table('categories')->where('id', $category->id)->update(['code' => $code]);
                        break 2;
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('categories', 'code')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('code');
            });
        }
    }
};
