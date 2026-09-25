<?php

namespace App\Services;

use App\Enums\QuantityPieceStatus;
use App\Models\Product;
use App\Models\Quantity;
use App\Services\ProductPriceCalculator;
use App\Services\SlugService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        protected SlugService $slugService,
        protected ProductPriceCalculator $calculator
    ) {}

    public function save(Product $product, Request $request): Product
    {
        return DB::transaction(function () use ($product, $request) {
            $name = (string) $request->input('name');
            $slugCandidate = $request->filled('slug') ? (string) $request->input('slug') : $name;

            $product->name = $name;
            $product->slug = $this->slugService->makeUnique(Product::class, $slugCandidate, $product->id, 'slug');
            $product->table = $request->input('table');
            $product->description = $request->input('desc');
            $product->excerpt = $request->input('excerpt');
            $product->addon = $request->input('addon', $product->addon ?? 0);
            $product->wage = $request->input('labor_charge_1', $request->input('wage', 0));
            $product->weight = $request->input('weight', 0);
            $product->labor_charge_1 = $request->input('labor_charge_1', $request->input('wage', 0));
            $product->labor_charge_2 = $request->input('labor_charge_2', 0);
            $product->labor_charge_3 = $request->input('labor_charge_3', 0);
            $product->profit = $request->input('profit', 0);
            $product->tax = $request->input('tax', 0);
            $product->min_stock_level = $request->input('min_stock_level', 0);
            $product->target_group = $request->input('target_group', 'unisex');
            $product->metal_type = $request->input('metal_type', 'gold');
            $product->plating_colors = array_values(array_filter((array) $request->input('plating_colors', [])));
            $product->stones = array_values(array_filter((array) $request->input('stones', [])));
            $product->accessories = array_values(array_filter((array) $request->input('accessories', [])));
            $product->occasions = array_values(array_filter((array) $request->input('occasions', [])));
            $product->keyword = $request->input('keyword');
            $product->stock_status = $request->input('stock_status', $product->stock_status ?? 'IN_STOCK');
            $product->price = $request->input('price', $product->price ?? 0);
            $product->buy_price = $request->input('buy_price', 0);

            if (! $request->has('quantity')) {
                $product->price = $request->input('price', $product->price ?? 0);
                $product->stock_quantity = $request->input('stock_quantity', $product->stock_quantity ?? 0);
            }
            $product->average_rating = $request->input('average_rating', 0);
            $product->rating_count = $request->input('rating_count', 0);
            $product->category_id = $request->input('category_id');
            $product->sku = $request->input('sku', $product->sku);
            $product->virtual = (bool) $request->input('virtual', false);
            $product->downloadable = (bool) $request->input('downloadable', false);
            $product->image_index = $request->input('index_image', 0);
            $product->user_id = auth()->id();
            $product->status = (int) $request->input('status', $product->status ?? 0);

            if ($request->has('canonical') && trim((string) $request->input('canonical')) !== '') {
                $product->canonical = $request->input('canonical');
            }

            $product->save();

            if ($request->has('cat')) {
                $product->categories()->sync((array) $request->input('cat', []));
            }

            $tags = array_filter(explode(',,', (string) $request->input('tags')));
            if (count($tags) > 0) {
                $product->syncTags($tags);
            }

            $mediaIds = (array) $request->input('medias', []);
            foreach ($product->getMedia() as $media) {
                if (! in_array($media->id, $mediaIds, false)) {
                    $media->delete();
                }
            }

            foreach ($request->file('image', []) as $image) {
                try {
                    $product->addMedia($image)
                        ->preservingOriginal()
                        ->toMediaCollection();
                } catch (\Throwable) {
                }
            }

            if ($request->has('meta')) {
                $product->syncMeta(json_decode((string) $request->get('meta', '[]'), true));
            }

            if ($request->has('stock_items')) {
                $this->syncStockItems($product, (string) $request->input('stock_items'), $this->calculator);
            } else {
                $toRemoveQ = $product->quantities()->pluck('id')->toArray();
                if ($request->has('q')) {
                    $qz = json_decode((string) $request->input('q'));
                    if (is_array($qz)) {
                        foreach ($qz as $qi) {
                            if ($qi->id == null) {
                                $q = new Quantity;
                            } else {
                                $q = Quantity::whereId($qi->id)->first();
                                $searchKey = array_search($q?->id, $toRemoveQ, true);
                                if ($searchKey !== false) {
                                    unset($toRemoveQ[$searchKey]);
                                }
                            }
                            if ($q) {
                                $q->image = $qi->image ?? null;
                                $q->count = $qi->count ?? 1;
                                $q->price = $qi->price ?? 0;
                                $q->product_id = $product->id;
                                $q->data = json_encode($qi->data ?? []);
                                if (isset($qi->data->weight)) {
                                    $q->weight = $qi->data->weight;
                                }
                                $q->save();
                            }
                        }
                    }
                    $product->quantities()->whereIn('id', $toRemoveQ)->delete();
                }
            }

            $this->calculator->repriceProduct($product->fresh(['quantities']));

            return $product->fresh();
        });
    }

    public function syncStockItems(Product $product, string $payload, ProductPriceCalculator $calculator): void
    {
        $items = json_decode($payload, true);
        if (! is_array($items)) {
            return;
        }

        $items = Quantity::assignPieceSkus($items, (string) $product->sku);
        $keepIds = [];

        foreach ($items as $item) {
            $id = $item['id'] ?? null;
            $weight = isset($item['weight']) ? (float) $item['weight'] : 0;

            if ($weight <= 0) {
                if (! empty($id)) {
                    $keepIds[] = (int) $id;
                }

                continue;
            }

            $quantity = $id ? Quantity::query()->where('product_id', $product->id)->whereKey($id)->first() : null;
            if ($quantity === null) {
                $quantity = new Quantity;
                $quantity->product_id = $product->id;
                $quantity->count = 1;
            }

            $rawStatus = isset($item['status']) ? (string) $item['status'] : null;
            if ($rawStatus === QuantityPieceStatus::Scrapped->value) {
                $quantity->status = QuantityPieceStatus::Scrapped;
                $quantity->count = 0;
            } elseif ($rawStatus === QuantityPieceStatus::Sold->value || (array_key_exists('count', $item) && (int) $item['count'] <= 0)) {
                $quantity->status = QuantityPieceStatus::Sold;
                $quantity->count = 0;
            } else {
                $quantity->status = QuantityPieceStatus::Available;
                $quantity->count = 1;
            }

            $quantity->weight = $weight;
            $quantity->code = isset($item['code']) && $item['code'] !== '' ? (string) $item['code'] : null;
            $quantity->image = $item['image'] ?? $quantity->image;
            $quantity->data = json_encode(array_filter([
                'weight' => $weight,
                'code' => $quantity->code,
                'status' => $quantity->status->value,
            ], fn ($value) => $value !== null && $value !== ''));
            $quantity->price = $calculator->calculate($product, $weight);
            $quantity->save();

            $keepIds[] = $quantity->id;
        }

        if ($keepIds === []) {
            $product->quantities()->delete();
        } else {
            $product->quantities()->whereNotIn('id', $keepIds)->delete();
        }
    }
}
