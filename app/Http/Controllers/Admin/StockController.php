<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Models\Product;
use App\Models\Quantity;
use App\Services\AdminDashboardStats;
use Illuminate\Support\Facades\DB;

class StockController extends XController
{
    protected $cols = ['name', 'sku', 'metal_type', 'stock_quantity', 'total_weight', 'total_price'];

    protected $extra_cols = ['id', 'slug', 'image_index', 'min_stock_level', 'buy_price', 'weight', 'price'];

    protected $searchable = ['name', 'slug', 'sku'];

    protected $listView = 'admin.stock.stock-list';

    protected $buttons = [
        'edit' => [
            'title' => 'Edit product',
            'class' => 'btn-outline-primary',
            'icon' => 'ri-edit-2-line',
            'route' => 'admin.product.edit',
        ],
        'show' => [
            'title' => 'Detail',
            'class' => 'btn-outline-secondary',
            'icon' => 'ri-eye-line',
            'route' => 'admin.product.show',
        ],
    ];

    public function __construct()
    {
        parent::__construct(Product::class, null);
    }

    protected function makeSortAndFilter()
    {
        $lowStock = request()->input('filter.low_stock');
        $belowBuyPrice = request()->input('filter.below_buy_price');

        if (($lowStock !== null && $lowStock !== '') || ($belowBuyPrice !== null && $belowBuyPrice !== '')) {
            $filters = request()->input('filter', []);
            unset($filters['low_stock'], $filters['below_buy_price']);
            request()->merge(['filter' => $filters]);
        }

        $sort = request()->input('sort');
        $customSort = null;
        if (in_array($sort, ['total_weight', 'total_price'], true)) {
            $customSort = $sort;
            request()->request->remove('sort');
        }

        $query = parent::makeSortAndFilter();

        if ($customSort !== null) {
            $sortType = strtolower(request()->input('sortType', 'asc')) === 'desc' ? 'desc' : 'asc';
            if ($customSort === 'total_weight') {
                $query->reorder()->orderByRaw('(COALESCE(weight, 0) * stock_quantity) '.$sortType);
            } elseif ($customSort === 'total_price') {
                $query->reorder()->orderByRaw('(COALESCE(price, 0) * stock_quantity) '.$sortType);
            }
            request()->merge(['sort' => $customSort]);
        }

        // Filter only items currently in stock
        $query->where('stock_quantity', '>', 0);

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

            request()->merge([
                'filter' => array_merge(request()->input('filter', []), ['low_stock' => $lowStock]),
            ]);
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

            request()->merge([
                'filter' => array_merge(request()->input('filter', []), ['below_buy_price' => $belowBuyPrice]),
            ]);
        }

        return $query;
    }

    protected function showList($query)
    {
        $quickCounts = [
            'all' => Product::query()->where('stock_quantity', '>', 0)->count(),
            'gold' => Product::query()->where('stock_quantity', '>', 0)->where('metal_type', 'gold')->count(),
            'silver' => Product::query()->where('stock_quantity', '>', 0)->where('metal_type', 'silver')->count(),
            'low_stock' => Product::query()->where('stock_quantity', '>', 0)->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<', 'min_stock_level')->count(),
            'below_buy_price' => Product::query()->where('stock_quantity', '>', 0)->where('buy_price', '>', 0)->whereColumn('price', '<', 'buy_price')->count(),
        ];

        $stockStats = $this->stockInventoryStats();

        $items = $query->with('quantities')->paginate(config('app.panel.page_count'), ['*']);
        $cols = $this->cols;
        $buttons = $this->buttons;

        return view($this->listView, compact('items', 'cols', 'buttons', 'quickCounts', 'stockStats'));
    }

    public function stockInventoryStats(): array
    {
        $dashboardStats = app(AdminDashboardStats::class);
        $stockStats = $dashboardStats->stockStats();

        $quantityValue = (int) Quantity::query()
            ->join('products', 'quantities.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('quantities.count', '>', 0)
            ->sum(DB::raw('COALESCE(quantities.price, 0) * quantities.count'));

        $productOnlyValue = (int) Product::query()
            ->whereDoesntHave('quantities')
            ->where('stock_status', 'IN_STOCK')
            ->where('stock_quantity', '>', 0)
            ->sum(DB::raw('COALESCE(price, 0) * stock_quantity'));

        $totalValue = $quantityValue + $productOnlyValue;
        $inStockProductsCount = Product::query()->where('stock_quantity', '>', 0)->count();

        return array_merge($stockStats, [
            'total_value' => $totalValue,
            'in_stock_products_count' => $inStockProductsCount,
        ]);
    }
}
