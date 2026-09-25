<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductSaveRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    use RespondsWithAdmin;
    use ResolvesAdminModel;

    protected array $cols = ['name', 'sku', 'weight', 'category_id', 'stock_quantity', 'status'];

    protected array $extraCols = ['id', 'slug', 'image_index', 'min_stock_level', 'price', 'buy_price', 'plating_colors', 'stones', 'accessories', 'occasions', 'metal_type', 'target_group'];

    protected array $searchable = ['name', 'slug', 'description', 'excerpt', 'sku', 'table'];

    public function index(Request $request, AdminTableService $tableService): View
    {
        $lowStock = $request->input('filter.low_stock');
        $belowBuyPrice = $request->input('filter.below_buy_price');

        $query = Product::query()->with(['availableQuantities']);

        if ($lowStock !== null && $lowStock !== '') {
            if ((string) $lowStock === '1') {
                $query->where('min_stock_level', '>', 0)
                    ->whereColumn('stock_quantity', '<', 'min_stock_level');
            } elseif ((string) $lowStock === '0') {
                $query->where(function ($q) {
                    $q->where('min_stock_level', '<=', 0)
                        ->orWhereNull('min_stock_level')
                        ->orWhereColumn('stock_quantity', '>=', 'min_stock_level');
                });
            }
        }

        if ($belowBuyPrice !== null && $belowBuyPrice !== '') {
            if ((string) $belowBuyPrice === '1') {
                $query->where('buy_price', '>', 0)
                    ->whereColumn('price', '<', 'buy_price');
            } elseif ((string) $belowBuyPrice === '0') {
                $query->where(function ($q) {
                    $q->where('buy_price', '<=', 0)
                        ->orWhereNull('buy_price')
                        ->orWhereColumn('price', '>=', 'buy_price');
                });
            }
        }

        if ($lowStock !== null && $lowStock !== '' || $belowBuyPrice !== null && $belowBuyPrice !== '') {
            $filters = (array) $request->input('filter', []);
            unset($filters['low_stock'], $filters['below_buy_price']);
            $request->merge(['filter' => $filters]);
        }

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->searchable($this->searchable)
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-close-line'],
                'category' => ['title' => 'Edit category', 'class' => 'btn-outline-info edit-category-btn', 'icon' => 'ri-list-check-3'],
            ])
            ->withQuickCounts([
                'gold' => fn () => Product::query()->where('metal_type', 'gold')->count(),
                'silver' => fn () => Product::query()->where('metal_type', 'silver')->count(),
                'low_stock' => fn () => Product::query()->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<', 'min_stock_level')->count(),
                'below_buy_price' => fn () => Product::query()->where('buy_price', '>', 0)->whereColumn('price', '<', 'buy_price')->count(),
            ])
            ->build($request);

        if ($lowStock !== null && $lowStock !== '') {
            $request->merge(['filter' => array_merge((array) $request->input('filter', []), ['low_stock' => $lowStock])]);
        }
        if ($belowBuyPrice !== null && $belowBuyPrice !== '') {
            $request->merge(['filter' => array_merge((array) $request->input('filter', []), ['below_buy_price' => $belowBuyPrice])]);
        }

        return view('admin.products.product-list', $tableData);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $lowStock = $request->input('filter.low_stock');
        $belowBuyPrice = $request->input('filter.below_buy_price');

        $query = Product::query()->onlyTrashed()->with(['availableQuantities']);

        if ($lowStock !== null && $lowStock !== '') {
            if ((string) $lowStock === '1') {
                $query->where('min_stock_level', '>', 0)
                    ->whereColumn('stock_quantity', '<', 'min_stock_level');
            } elseif ((string) $lowStock === '0') {
                $query->where(function ($q) {
                    $q->where('min_stock_level', '<=', 0)
                        ->orWhereNull('min_stock_level')
                        ->orWhereColumn('stock_quantity', '>=', 'min_stock_level');
                });
            }
        }

        if ($belowBuyPrice !== null && $belowBuyPrice !== '') {
            if ((string) $belowBuyPrice === '1') {
                $query->where('buy_price', '>', 0)
                    ->whereColumn('price', '<', 'buy_price');
            } elseif ((string) $belowBuyPrice === '0') {
                $query->where(function ($q) {
                    $q->where('buy_price', '<=', 0)
                        ->orWhereNull('buy_price')
                        ->orWhereColumn('price', '>=', 'buy_price');
                });
            }
        }

        if ($lowStock !== null && $lowStock !== '' || $belowBuyPrice !== null && $belowBuyPrice !== '') {
            $filters = (array) $request->input('filter', []);
            unset($filters['low_stock'], $filters['below_buy_price']);
            $request->merge(['filter' => $filters]);
        }

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->searchable($this->searchable)
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->withQuickCounts([
                'gold' => fn () => Product::query()->where('metal_type', 'gold')->count(),
                'silver' => fn () => Product::query()->where('metal_type', 'silver')->count(),
                'low_stock' => fn () => Product::query()->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<', 'min_stock_level')->count(),
                'below_buy_price' => fn () => Product::query()->where('buy_price', '>', 0)->whereColumn('price', '<', 'buy_price')->count(),
            ])
            ->build($request);

        return view('admin.products.product-list', $tableData);
    }

    public function create(): View
    {
        $cats = Category::all(['id', 'name', 'parent_id', 'code']);

        return view('admin.products.product-form', compact('cats'));
    }

    public function store(ProductSaveRequest $request, ProductService $productService): JsonResponse|RedirectResponse
    {
        $product = new Product;
        $savedItem = $productService->save($product, $request);
        logAdmin(__METHOD__, Product::class, $savedItem->id);

        return $this->respondAfterSave($request, $savedItem, __('As you wished created successfully'), 'admin.product.edit');
    }

    public function show(Product|string|int $item): RedirectResponse
    {
        $product = $this->resolveProduct($item);
        if ($product && method_exists($product, 'webUrl')) {
            return redirect($product->webUrl());
        }

        return redirect()->route('admin.product.index');
    }

    public function edit(Product|string|int $item): View
    {
        $item = $this->resolveProduct($item);
        $cats = Category::all(['id', 'name', 'parent_id', 'code']);

        return view('admin.products.product-form', compact('item', 'cats'));
    }

    public function update(ProductSaveRequest $request, Product|string|int $item, ProductService $productService): JsonResponse|RedirectResponse
    {
        $product = $this->resolveProduct($item);
        $savedItem = $productService->save($product, $request);
        logAdmin(__METHOD__, Product::class, $savedItem->id);

        return $this->respondAfterSave($request, $savedItem, __('As you wished updated successfully'), 'admin.product.edit');
    }

    public function destroy(Product|string|int $item): RedirectResponse
    {
        $product = $this->resolveProduct($item);
        logAdmin(__METHOD__, Product::class, $product->id);
        $product->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function restore(Product|string|int $item): RedirectResponse
    {
        $product = $this->resolveProduct($item, true);
        logAdmin(__METHOD__, Product::class, $product->id);
        $product->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(
            Product::class,
            $request->input('action'),
            (array) $request->input('id', []),
            function (string $action, ?string $subAction, array $ids): ?string {
                if ($action === 'publish') {
                    Product::whereIn('id', $ids)->update(['status' => 1]);

                    return __(':COUNT items published successfully', ['COUNT' => count($ids)]);
                }

                if ($action === 'draft') {
                    Product::whereIn('id', $ids)->update(['status' => 0]);

                    return __(':COUNT items drafted successfully', ['COUNT' => count($ids)]);
                }

                return null;
            }
        );
    }

    public function categoryEdit($id): View
    {
        $product = $this->resolveProduct($id);
        $cats = Category::all(['id', 'name', 'parent_id', 'code']);

        return view('admin.products.category-edit', compact('product', 'cats'));
    }

    public function categorySave(Product|string|int $item, Request $request): JsonResponse|RedirectResponse
    {
        $product = $this->resolveProduct($item);
        $product->categories()->sync((array) $request->input('cat', []));
        logAdmin(__METHOD__, Product::class, $product->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['OK' => true, 'message' => __('Categories saved successfully')]);
        }

        return redirect()->back()->with(['message' => __('Categories saved successfully')]);
    }

    public function updateTitle(Request $request): RedirectResponse
    {
        return redirect()->back();
    }

    protected function resolveProduct(Product|string|int $item, bool $withTrashed = false): Product
    {
        return $this->resolveModel(Product::class, $item, $withTrashed);
    }
}
