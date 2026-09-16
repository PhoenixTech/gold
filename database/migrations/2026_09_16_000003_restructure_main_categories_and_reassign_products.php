<?php

use App\Models\Product;
use App\Models\Quantity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        // 1. Define the 11 official main categories according to sku-2.md
        $mainCategoriesData = [
            'Gr' => [
                'code' => 'Gr',
                'slug' => 'necklaces-pendants',
                'name' => json_encode(['fa' => 'گردنبند و آویز', 'en' => 'Necklaces & Pendants'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-necklace-line',
            ],
            'E' => [
                'code' => 'E',
                'slug' => 'earrings',
                'name' => json_encode(['fa' => 'گوشواره', 'en' => 'Earrings'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-disc-line',
            ],
            'A' => [
                'code' => 'A',
                'slug' => 'rings',
                'name' => json_encode(['fa' => 'انگشتر', 'en' => 'Rings'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-copper-diamond-line',
            ],
            'D' => [
                'code' => 'D',
                'slug' => 'bracelets',
                'name' => json_encode(['fa' => 'دستبند', 'en' => 'Bracelets'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-hand-coin-line',
            ],
            'L' => [
                'code' => 'L',
                'slug' => 'bangles',
                'name' => json_encode(['fa' => 'النگو', 'en' => 'Bangles'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-radio-button-line',
            ],
            'P' => [
                'code' => 'P',
                'slug' => 'anklets',
                'name' => json_encode(['fa' => 'پابند', 'en' => 'Anklets'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-footprint-line',
            ],
            'Z' => [
                'code' => 'Z',
                'slug' => 'chains',
                'name' => json_encode(['fa' => 'زنجیر', 'en' => 'Chains'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-link',
            ],
            'Ac' => [
                'code' => 'Ac',
                'slug' => 'accessories',
                'name' => json_encode(['fa' => 'اکسسوری', 'en' => 'Accessories'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-gift-line',
            ],
            'Pr' => [
                'code' => 'Pr',
                'slug' => 'piercings',
                'name' => json_encode(['fa' => 'پیرسینگ', 'en' => 'Piercings'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-drop-line',
            ],
            'Set' => [
                'code' => 'Set',
                'slug' => 'sets',
                'name' => json_encode(['fa' => 'ست', 'en' => 'Sets'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-box-3-line',
            ],
            'Sh' => [
                'code' => 'Sh',
                'slug' => 'ingots',
                'name' => json_encode(['fa' => 'شمش', 'en' => 'Ingots'], JSON_UNESCAPED_UNICODE),
                'icon' => 'ri-vip-diamond-line',
            ],
        ];

        // 2. Insert the 11 main categories
        $mainCategoryMap = [];
        $now = now();
        $sort = 1;
        foreach ($mainCategoriesData as $code => $data) {
            $id = DB::table('categories')->insertGetId([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'code' => $data['code'],
                'icon' => $data['icon'],
                'bg_color' => '#ffffff',
                'color' => '#000000',
                'sort' => $sort++,
                'hide' => 0,
                'parent_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $mainCategoryMap[$code] = $id;
        }

        // 3. Keyword rules to identify the nearest main category for each product
        $categoryKeywords = [
            'Gr' => ['گردنبند', 'آویز', 'necklace', 'pendant'],
            'E' => ['گوشواره', 'earring'],
            'A' => ['انگشتر', 'حلقه', 'ring'],
            'L' => ['النگو', 'bangle'],
            'D' => ['دستبند', 'bracelet'],
            'P' => ['پابند', 'خلخال', 'anklet'],
            'Z' => ['زنجیر', 'chain'],
            'Pr' => ['پیرسینگ', 'piercing'],
            'Set' => ['نیم ست', 'نیم‌ست', 'سرویس', 'ست', 'set'],
            'Sh' => ['شمش', 'ingot', 'bar'],
            'Ac' => ['اکسسوری', 'accessory', 'سنجاق', 'تاج', 'چشم نظر', 'دکمه سردست'],
        ];

        $targetKeywords = [
            'women' => ['زنانه'],
            'men' => ['مردانه'],
            'children' => ['بچه‌گانه', 'بچه گانه', 'نوزاد', 'کودک'],
        ];
        $targetLetters = [
            'women' => 'F',
            'men' => 'M',
            'children' => 'C',
        ];

        // 4. Update products to the nearest main category and regenerate SKU
        $products = DB::table('products')->orderBy('id')->get();
        $sequencePerCategory = [];

        foreach ($products as $p) {
            $matchedCode = null;
            $pText = $p->name.' '.($p->slug ?? '');

            // First, try matching keywords directly from the product title / slug
            foreach ($categoryKeywords as $code => $keywords) {
                foreach ($keywords as $kw) {
                    if (mb_stripos($pText, $kw) !== false) {
                        $matchedCode = $code;
                        break 2;
                    }
                }
            }

            // If not matched, try matching the product's old category name or code
            if (! $matchedCode && $p->category_id) {
                $oldCat = DB::table('categories')->where('id', $p->category_id)->first();
                if ($oldCat) {
                    if (! empty($oldCat->code) && isset($mainCategoryMap[$oldCat->code])) {
                        $matchedCode = $oldCat->code;
                    } else {
                        $catText = (string) $oldCat->name.' '.(string) ($oldCat->slug ?? '');
                        foreach ($categoryKeywords as $code => $keywords) {
                            foreach ($keywords as $kw) {
                                if (mb_stripos($catText, $kw) !== false) {
                                    $matchedCode = $code;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            $matchedCode = $matchedCode ?? 'Ac';
            $newCatId = $mainCategoryMap[$matchedCode];

            // Infer target group (gender) if unisex or missing
            $targetGroup = $p->target_group ?? 'women';
            foreach ($targetKeywords as $tg => $kws) {
                foreach ($kws as $kw) {
                    if (mb_stripos($pText, $kw) !== false) {
                        $targetGroup = $tg;
                        break 2;
                    }
                }
            }
            $t = $targetLetters[$targetGroup] ?? 'F';
            $m = ($p->metal_type === 'silver') ? '2' : '1';

            $sequencePerCategory[$newCatId] = ($sequencePerCategory[$newCatId] ?? 0) + 1;
            $n = sprintf('%04d', $sequencePerCategory[$newCatId]);

            $newSku = "{$t}{$m}{$matchedCode}{$n}";

            DB::table('products')->where('id', $p->id)->update([
                'category_id' => $newCatId,
                'target_group' => $targetGroup,
                'sku' => $newSku,
            ]);

            // Sync pivot table
            DB::table('category_product')->where('product_id', $p->id)->delete();
            DB::table('category_product')->insert([
                'category_id' => $newCatId,
                'product_id' => $p->id,
            ]);

            // Update piece codes in quantities
            $quantities = DB::table('quantities')->where('product_id', $p->id)->orderBy('id')->get();
            foreach ($quantities as $idx => $q) {
                $pieceSku = Quantity::pieceSku($newSku, $idx + 1);
                DB::table('quantities')->where('id', $q->id)->update(['code' => $pieceSku]);
            }
        }

        // 5. Delete all old categories and clean references
        $keptIds = array_values($mainCategoryMap);
        DB::table('category_prop')->whereNotIn('category_id', $keptIds)->delete();
        DB::table('category_product')->whereNotIn('category_id', $keptIds)->delete();
        DB::table('evaluations')
            ->where('evaluationable_type', 'App\Models\Category')
            ->whereNotIn('evaluationable_id', $keptIds)
            ->delete();
        DB::table('categories')->whereNotIn('id', $keptIds)->delete();
        DB::table('categories')->whereIn('id', $keptIds)->update(['parent_id' => null]);

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way migration
    }
};
