<?php

use App\Models\Product;
use App\Models\Quantity;
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
        Schema::disableForeignKeyConstraints();

        if (! Schema::hasColumn('categories', 'code')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('code', 10)->nullable()->after('slug')->index();
            });
        }

        if (! Schema::hasColumn('categories', 'silver_image')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('silver_image', 2048)->nullable()->after('image');
            });
        }

        // 0. Clear all product SKUs to prevent unique constraint violations during reassignment
        DB::table('products')->update(['sku' => null]);

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

        // 2. Upsert the 11 main categories (update if slug exists, insert otherwise)
        $mainCategoryMap = [];
        $now = now();
        $sort = 1;
        foreach ($mainCategoriesData as $code => $data) {
            $existing = DB::table('categories')->where('slug', $data['slug'])->first();

            if ($existing) {
                DB::table('categories')->where('id', $existing->id)->update([
                    'name' => $data['name'],
                    'code' => $data['code'],
                    'icon' => $data['icon'],
                    'bg_color' => '#ffffff',
                    'color' => '#000000',
                    'sort' => $sort++,
                    'hide' => 0,
                    'parent_id' => null,
                    'updated_at' => $now,
                ]);
                $id = $existing->id;
            } else {
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
            }

            $mainCategoryMap[$code] = $id;
        }

        // 3. Keyword rules to identify category, metal, and gender
        $categoryKeywords = [
            'Gr' => ['گردنبند', 'آویز', 'پلاک', 'مدال', 'necklace', 'pendant'],
            'E' => ['گوشواره', 'earring'],
            'A' => ['انگشتر', 'حلقه', 'ring'],
            'L' => ['النگو', 'تک پوش', 'تکپوش', 'bangle'],
            'D' => ['دستبند', 'bracelet'],
            'P' => ['پابند', 'خلخال', 'anklet'],
            'Z' => ['زنجیر', 'chain'],
            'Pr' => ['پیرسینگ', 'piercing'],
            'Set' => ['نیم ست', 'نیم‌ست', 'سرویس', 'ست', 'set', 'service'],
            'Sh' => ['شمش', 'ingot', 'bar'],
            'Ac' => ['اکسسوری', 'accessory', 'تاج', 'crown', 'سنجاق', 'چشم نظر', 'دکمه سردست', 'گیره'],
        ];

        $targetKeywords = [
            'women' => ['زنانه', 'بانوان', 'دخترانه', 'دختر', 'women', 'woman', 'female', 'lady'],
            'men' => ['مردانه', 'آقایان', 'پسرانه', 'پسر', 'men', 'man', 'male'],
            'children' => ['بچه‌گانه', 'بچه گانه', 'نوزاد', 'کودک', 'children', 'child', 'kid', 'baby'],
        ];

        $targetLetters = [
            'women' => 'F',
            'men' => 'M',
            'children' => 'C',
        ];

        // 4. Update products: detect metal_type, gender, main category and regenerate SKU
        $products = DB::table('products')->orderBy('id')->get();
        $sequencePerCategory = [];

        foreach ($products as $p) {
            // Gather all available textual context from product & old categories
            $relatedCatIds = DB::table('category_product')->where('product_id', $p->id)->pluck('category_id')->toArray();
            if ($p->category_id) {
                $relatedCatIds[] = $p->category_id;
            }
            $relatedCatIds = array_unique(array_filter($relatedCatIds));

            $oldCats = DB::table('categories')->whereIn('id', $relatedCatIds)->get();
            $parentCatIds = $oldCats->pluck('parent_id')->filter()->toArray();
            $parentCats = ! empty($parentCatIds) ? DB::table('categories')->whereIn('id', $parentCatIds)->get() : collect();

            $allText = $p->name.' '.($p->slug ?? '').' '.($p->description ?? '').' '.($p->excerpt ?? '').' ';
            foreach ($oldCats->merge($parentCats) as $cat) {
                $allText .= $cat->name.' '.($cat->slug ?? '').' '.($cat->code ?? '').' ';
            }

            // A. Detect Metal Type (Silver vs Gold)
            $isSilver = (
                strtolower((string) $p->metal_type) === 'silver'
                || mb_stripos($allText, 'نقره') !== false
                || mb_stripos($allText, 'silver') !== false
            );
            $metalType = $isSilver ? 'silver' : 'gold';

            // B. Detect Main Category Code
            $matchedCode = null;
            // Check direct category code match first
            foreach ($oldCats as $cat) {
                if (! empty($cat->code) && isset($mainCategoryMap[$cat->code])) {
                    $matchedCode = $cat->code;
                    break;
                }
            }

            if (! $matchedCode) {
                foreach ($categoryKeywords as $code => $keywords) {
                    foreach ($keywords as $kw) {
                        if (mb_stripos($allText, $kw) !== false) {
                            $matchedCode = $code;
                            break 2;
                        }
                    }
                }
            }
            $matchedCode = $matchedCode ?? 'Ac';
            $newCatId = $mainCategoryMap[$matchedCode];

            // C. Detect Target Group (Gender)
            $targetGroup = $p->target_group;
            if (! in_array($targetGroup, ['women', 'men', 'children'])) {
                $targetGroup = null;
            }
            if (! $targetGroup) {
                foreach ($targetKeywords as $tg => $kws) {
                    foreach ($kws as $kw) {
                        if (mb_stripos($allText, $kw) !== false) {
                            $targetGroup = $tg;
                            break 2;
                        }
                    }
                }
            }
            $targetGroup = $targetGroup ?? 'women';

            // D. Generate SKU (1 for gold, 2 for silver)
            $t = $targetLetters[$targetGroup] ?? 'F';
            $m = ($metalType === 'silver') ? '2' : '1';

            $skuPrefix = "{$t}{$m}{$matchedCode}";
            $sequencePerCategory[$skuPrefix] = ($sequencePerCategory[$skuPrefix] ?? 0) + 1;
            $n = sprintf('%04d', $sequencePerCategory[$skuPrefix]);

            $newSku = "{$skuPrefix}{$n}";

            DB::table('products')->where('id', $p->id)->update([
                'category_id' => $newCatId,
                'target_group' => $targetGroup,
                'metal_type' => $metalType,
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

        // 5. Transfer images (both Gold and Silver), svgs, bgs, and descriptions from old categories to matching new categories
        $keptIds = array_values($mainCategoryMap);

        foreach ($mainCategoryMap as $code => $newId) {
            $keywords = $categoryKeywords[$code] ?? [];
            $allOldCategories = DB::table('categories')->whereNotIn('id', $keptIds)->get();

            $updateData = [];
            $newCat = DB::table('categories')->where('id', $newId)->first();
            $matchedOldIds = [];

            foreach ($allOldCategories as $oldCat) {
                $catText = (string) $oldCat->name.' '.(string) ($oldCat->slug ?? '').' '.(string) ($oldCat->code ?? '');
                $matched = (! empty($oldCat->code) && $oldCat->code === $code);

                if (! $matched) {
                    foreach ($keywords as $kw) {
                        if (mb_stripos($catText, $kw) !== false) {
                            $matched = true;
                            break;
                        }
                    }
                }

                if ($matched) {
                    $matchedOldIds[] = $oldCat->id;

                    $isOldCatSilver = (
                        mb_stripos($catText, 'نقره') !== false
                        || mb_stripos($catText, 'silver') !== false
                    );

                    if ($isOldCatSilver) {
                        // Silver category image -> silver_image
                        if (empty($newCat->silver_image) && empty($updateData['silver_image']) && ! empty($oldCat->image)) {
                            $updateData['silver_image'] = $oldCat->image;
                        }
                    } else {
                        // Gold category image -> image
                        if (empty($newCat->image) && empty($updateData['image']) && ! empty($oldCat->image)) {
                            $updateData['image'] = $oldCat->image;
                        }
                    }

                    if (empty($newCat->svg) && empty($updateData['svg']) && ! empty($oldCat->svg)) {
                        $updateData['svg'] = $oldCat->svg;
                    }
                    if (empty($newCat->bg) && empty($updateData['bg']) && ! empty($oldCat->bg)) {
                        $updateData['bg'] = $oldCat->bg;
                    }
                    if (empty($newCat->description) && empty($updateData['description']) && ! empty($oldCat->description)) {
                        $updateData['description'] = $oldCat->description;
                    }
                }
            }

            // Fallback: if silver_image is empty, use image; if image is empty, use silver_image
            if (empty($newCat->image) && empty($updateData['image']) && ! empty($updateData['silver_image'])) {
                $updateData['image'] = $updateData['silver_image'];
            }

            if (! empty($updateData)) {
                DB::table('categories')->where('id', $newId)->update($updateData);
            }

            if (! empty($matchedOldIds)) {
                DB::table('attachments')
                    ->where('attachable_type', 'App\Models\Category')
                    ->whereIn('attachable_id', $matchedOldIds)
                    ->update(['attachable_id' => $newId]);

                DB::table('evaluations')
                    ->where('evaluationable_type', 'App\Models\Category')
                    ->whereIn('evaluationable_id', $matchedOldIds)
                    ->update(['evaluationable_id' => $newId]);
            }
        }

        // 6. Delete all old categories and clean references
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
