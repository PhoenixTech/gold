<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Http\Requests\ProductSaveRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quantity;
use App\Services\ProductPriceCalculator;
use Illuminate\Http\Request;

class ProductController extends XController
{
    // protected  $_MODEL_ = Product::class;
    // protected  $SAVE_REQUEST = ProductSaveRequest::class;

    protected $cols = ['name', 'sku', 'metal_type', 'target_group', 'weight', 'category_id', 'stock_quantity', 'status'];

    protected $extra_cols = ['id', 'slug', 'image_index'];

    protected $searchable = ['name', 'slug', 'description', 'excerpt', 'sku', 'table'];

    protected $listView = 'admin.products.product-list';

    protected $formView = 'admin.products.product-form';

    protected $buttons = [
        'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
        'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
        'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
        'category' => ['title' => 'Edit category', 'class' => 'btn-outline-info edit-category-btn', 'icon' => 'ri-list-check-3'],
    ];

    public function __construct()
    {
        parent::__construct(Product::class, ProductSaveRequest::class);
    }

    /**
     * @param  $product  Product
     * @param  $request  ProductSaveRequest
     * @return Product
     */
    public function save($product, $request)
    {

        //        dd($request->all());
        $product->name = $request->input('name');
        $product->slug = $this->getSlug($product, 'slug', 'name');

        $product->table = $request->input('table');
        $product->description = $request->input('desc');
        $product->excerpt = $request->input('excerpt');
        $product->addon = $request->input('addon');
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
        $product->keyword = $request->input('keyword');
        $product->stock_status = $request->input('stock_status');
        $product->price = $request->input('price', 0);
        $product->buy_price = $request->input('buy_price', 0);

        if (! $request->has('quantity')) {
            $product->price = $request->input('price', 0);
            $product->stock_quantity = $request->input('stock_quantity');
        }
        $product->average_rating = $request->input('average_rating', 0);
        $product->average_rating = $request->input('average_rating', 0);
        $product->rating_count = $request->input('rating_count', 0);
        $product->category_id = $request->input('category_id');
        $product->sku = Product::generateSku($product->target_group, $product->metal_type, $product->category_id, $product->id);
        $product->virtual = $request->input('virtual', false);
        $product->downloadable = $request->input('downloadable', false);
        $product->image_index = $request->input('index_image', 0);
        $product->user_id = auth()->id();
        $product->status = $request->input('status');
        $tags = array_filter(explode(',,', $request->input('tags')));
        if ($request->has('canonical') && trim($request->input('canonical')) != '') {
            $product->canonical = $request->input('canonical');
        }

        $product->save();
        $product->categories()->sync($request->input('cat'));
        if (count($tags) > 0) {
            $product->syncTags($tags);
        }

        foreach ($product->getMedia() as $media) {
            in_array($media->id, request('medias', [])) ?: $media->delete();
        }
        foreach ($request->file('image', []) as $image) {
            try {
                $product->addMedia($image)
                    ->preservingOriginal() // middle method
                    ->toMediaCollection(); // finishing method
            } catch (FileDoesNotExist $e) {
            } catch (FileIsTooBig $e) {
            }
        }

        if ($request->has('meta')) {
            //            dd($request->input('meta'));
            $product->syncMeta(json_decode($request->get('meta', '[]'), true));
        }
        $calculator = app(ProductPriceCalculator::class);

        if ($request->has('stock_items')) {
            $this->syncStockItems($product, $request->input('stock_items'), $calculator);
        } else {
            $toRemoveQ = $product->quantities()->pluck('id')->toArray();
            if ($request->has('q')) {
                $qz = json_decode($request->input('q'));
                foreach ($qz as $qi) {
                    if ($qi->id == null) {
                        $q = new Quantity;
                    } else {
                        $q = Quantity::whereId($qi->id)->first();
                        unset($toRemoveQ[array_search($q->id, $toRemoveQ)]); // remove for to remove IDz
                    }
                    $q->image = $qi->image;
                    $q->count = $qi->count;
                    $q->price = $qi->price;
                    $q->product_id = $product->id;
                    $q->data = json_encode($qi->data);
                    if (isset($qi->data->weight)) {
                        $q->weight = $qi->data->weight;
                    }
                    $q->save();
                }
                $product->quantities()->whereIn('id', $toRemoveQ)->delete();
            }
        }

        $calculator->repriceProduct($product->fresh(['quantities']));

        return $product->fresh();

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        $cats = Category::all(['id', 'name', 'parent_id']);

        return view($this->formView, compact('cats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $item)
    {
        //

        $cats = Category::all(['id', 'name', 'parent_id']);

        return view($this->formView, compact('item', 'cats'));
    }

    public function bulk(Request $request)
    {

        //        dd($request->all());
        $data = explode('.', $request->input('action'));
        $action = $data[0];
        $ids = $request->input('id');
        switch ($action) {
            case 'delete':
                $msg = __(':COUNT items deleted successfully', ['COUNT' => count($ids)]);
                $this->_MODEL_::destroy($ids);
                break;
                /**restore*/
            case 'restore':
                $msg = __(':COUNT items restored successfully', ['COUNT' => count($ids)]);
                foreach ($ids as $id) {
                    $this->_MODEL_::withTrashed()->find($id)->restore();
                }
                break;
                /* restore* */
            case 'publish':
                $this->_MODEL_::whereIn('id', $request->input('id'))->update(['status' => 1]);
                $msg = __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                break;
            case 'draft':
                $this->_MODEL_::whereIn('id', $request->input('id'))->update(['status' => 0]);
                $msg = __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                break;
            default:
                $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $action]);
        }

        return $this->do_bulk($msg, $action, $ids);
    }

    public function destroy(Product $item)
    {
        return parent::delete($item);
    }

    public function update(Request $request, Product $item)
    {
        return $this->bringUp($request, $item);
    }

    /**restore*/
    public function restore($item)
    {
        return parent::restoreing(Product::withTrashed()->where('id', $item)->first());
    }

    /* restore* */

    /**
     * @param  $id  Product's id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\View\View
     */
    public function categoryEdit($id)
    {

        $product = Product::find($id);
        $cats = Category::all(['id', 'name', 'parent_id']);

        return view('admin.products.category-edit', compact('product', 'cats'));
    }

    /**
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public function categorySave(Product $item, Request $request)
    {
        $item->categories()->sync($request->input('cat'));
        logAdmin(__METHOD__, __CLASS__, $item->id);
        if ($request->ajax()) {
            return ['OK' => true, 'message' => __('Categories saved successfully')];
        } else {
            return redirect()->back()->with(['message' => __('Categories saved successfully')]);
        }
    }

    /**
     * Sync unique stock pieces (weight + optional code) onto Quantity rows.
     */
    protected function syncStockItems(Product $product, string $payload, ProductPriceCalculator $calculator): void
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

            $isSold = array_key_exists('count', $item) && (int) $item['count'] <= 0;
            $quantity->weight = $weight;
            $quantity->code = isset($item['code']) && $item['code'] !== '' ? (string) $item['code'] : null;
            $quantity->count = $isSold ? 0 : 1;
            $quantity->image = $item['image'] ?? $quantity->image;
            $quantity->data = json_encode(array_filter([
                'weight' => $weight,
                'code' => $quantity->code,
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
