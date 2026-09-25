<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuantityPieceStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Quantity;
use App\Services\Admin\AdminTableService;
use App\Services\AdminDashboardStats;
use App\Services\ProductPriceCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    protected array $cols = [
        'name',
        'sku',
        'stock_quantity',
        'total_ordered',
        'scrapped_pieces',
        'sold_pieces',
        'total_weight',
        'total_price',
    ];

    protected array $searchable = ['name', 'slug', 'sku'];

    protected array $buttons = [
        'edit' => [
            'title' => 'Edit product',
            'class' => 'btn-outline-primary',
            'icon' => 'ri-edit-2-line',
            'route' => 'admin.product.edit',
        ],
    ];

    public function index(Request $request, AdminTableService $tableService): View
    {
        $lowStock = $request->input('filter.low_stock');
        $belowBuyPrice = $request->input('filter.below_buy_price');
        $stockCondition = $request->input('filter.stock_condition', 'in_stock');

        $query = Product::query()->with(['quantities', 'category']);

        $query->withCount([
            'quantities as total_ordered_count',
            'quantities as scrapped_pieces_count' => fn ($q) => $q->where('status', QuantityPieceStatus::Scrapped->value),
            'quantities as sold_pieces_count' => fn ($q) => $q->where(function ($sq) {
                $sq->where('status', QuantityPieceStatus::Sold->value)
                    ->orWhere(function ($fq) {
                        $fq->where('count', '<=', 0)
                            ->where('status', '!=', QuantityPieceStatus::Scrapped->value);
                    });
            }),
        ]);

        if ($stockCondition === 'in_stock') {
            $query->where('stock_quantity', '>', 0);
        } elseif ($stockCondition === 'has_scrapped') {
            $query->whereHas('quantities', fn ($q) => $q->where('status', QuantityPieceStatus::Scrapped->value));
        } elseif ($stockCondition === 'has_sold') {
            $query->whereHas('quantities', fn ($q) => $q->where(function ($sq) {
                $sq->where('status', QuantityPieceStatus::Sold->value)
                    ->orWhere(function ($fq) {
                        $fq->where('count', '<=', 0)
                            ->where('status', '!=', QuantityPieceStatus::Scrapped->value);
                    });
            }));
        } elseif ($stockCondition === 'out_of_stock') {
            $query->where('stock_quantity', '<=', 0);
        }

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

        $filters = (array) $request->input('filter', []);
        $cleanFilters = $filters;
        unset($cleanFilters['low_stock'], $cleanFilters['below_buy_price'], $cleanFilters['stock_condition']);
        $request->merge(['filter' => $cleanFilters]);

        $tableData = $tableService->for($query)
            ->columns($this->cols)
            ->selectColumns(['*'])
            ->searchable($this->searchable)
            ->buttons($this->buttons)
            ->withCustomSort(function (Builder $q, ?string $sort, string $sortType) {
                $customSorts = ['total_weight', 'total_price', 'most_sold', 'most_scrapped', 'total_ordered'];
                if (in_array($sort, $customSorts, true)) {
                    if ($sort === 'total_weight') {
                        $q->orderByRaw('(COALESCE(weight, 0) * stock_quantity) '.$sortType);
                    } elseif ($sort === 'total_price') {
                        $q->orderByRaw('(COALESCE(price, 0) * stock_quantity) '.$sortType);
                    } elseif ($sort === 'most_sold') {
                        $q->orderBy('sold_pieces_count', $sortType);
                    } elseif ($sort === 'most_scrapped') {
                        $q->orderBy('scrapped_pieces_count', $sortType);
                    } elseif ($sort === 'total_ordered') {
                        $q->orderBy('total_ordered_count', $sortType);
                    }

                    return true;
                }

                return false;
            })
            ->withQuickCounts([
                'in_stock' => fn () => Product::query()->where('stock_quantity', '>', 0)->count(),
                'has_scrapped' => fn () => Product::query()->whereHas('quantities', fn ($q) => $q->where('status', QuantityPieceStatus::Scrapped->value))->count(),
                'has_sold' => fn () => Product::query()->whereHas('quantities', fn ($q) => $q->where(function ($sq) {
                    $sq->where('status', QuantityPieceStatus::Sold->value)
                        ->orWhere(function ($fq) {
                            $fq->where('count', '<=', 0)
                                ->where('status', '!=', QuantityPieceStatus::Scrapped->value);
                        });
                }))->count(),
                'gold' => fn () => Product::query()->where('stock_quantity', '>', 0)->where('metal_type', 'gold')->count(),
                'silver' => fn () => Product::query()->where('stock_quantity', '>', 0)->where('metal_type', 'silver')->count(),
                'low_stock' => fn () => Product::query()->where('stock_quantity', '>', 0)->where('min_stock_level', '>', 0)->whereColumn('stock_quantity', '<', 'min_stock_level')->count(),
                'below_buy_price' => fn () => Product::query()->where('stock_quantity', '>', 0)->where('buy_price', '>', 0)->whereColumn('price', '<', 'buy_price')->count(),
            ])
            ->build($request);

        $request->merge(['filter' => array_merge($filters, ['stock_condition' => $stockCondition])]);
        $tableData['stockStats'] = $this->stockInventoryStats();

        return view('admin.stock.stock-list', $tableData);
    }

    public function stockInventoryStats(): array
    {
        $dashboardStats = app(AdminDashboardStats::class);
        $stockStats = $dashboardStats->stockStats();

        $quantityValue = (int) Quantity::query()
            ->join('products', 'quantities.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where(function ($q) {
                $q->where('quantities.status', QuantityPieceStatus::Available->value)
                    ->orWhereNull('quantities.status');
            })
            ->where('quantities.count', '>', 0)
            ->sum(DB::raw('COALESCE(quantities.price, 0) * quantities.count'));

        $productOnlyValue = (int) Product::query()
            ->whereDoesntHave('quantities')
            ->where('stock_status', 'IN_STOCK')
            ->where('stock_quantity', '>', 0)
            ->sum(DB::raw('COALESCE(price, 0) * stock_quantity'));

        $totalValue = $quantityValue + $productOnlyValue;

        $totalScrappedCount = (int) Quantity::query()
            ->where('status', QuantityPieceStatus::Scrapped->value)
            ->count();

        $totalSoldCount = (int) Quantity::query()
            ->where(function ($q) {
                $q->where('status', QuantityPieceStatus::Sold->value)
                    ->orWhere('count', '<=', 0);
            })
            ->count();

        return array_merge($stockStats, [
            'total_value' => $totalValue,
            'total_scrapped_count' => $totalScrappedCount,
            'total_sold_count' => $totalSoldCount,
        ]);
    }

    public function pieces($product): JsonResponse
    {
        $product = $product instanceof Product
            ? $product
            : (Product::find($product) ?? Product::where('slug', $product)->firstOrFail());

        $pieces = $product->quantities()
            ->orderBy('id')
            ->get(['id', 'weight', 'code', 'status', 'count', 'price', 'created_at'])
            ->map(function (Quantity $q) {
                $statusEnum = $q->status ?? ($q->count > 0 ? QuantityPieceStatus::Available : QuantityPieceStatus::Sold);

                return [
                    'id' => $q->id,
                    'code' => $q->code ?: '-',
                    'weight' => (float) ($q->weight ?? 0),
                    'price' => (int) ($q->price ?? 0),
                    'status' => $statusEnum->value,
                    'status_label' => $statusEnum->label(),
                    'badge_class' => $statusEnum->badgeClass(),
                    'is_scrapped' => $statusEnum === QuantityPieceStatus::Scrapped,
                    'is_sold' => $statusEnum === QuantityPieceStatus::Sold,
                    'is_available' => $statusEnum === QuantityPieceStatus::Available,
                ];
            });

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'stock_quantity' => (int) ($product->stock_quantity ?? 0),
            ],
            'pieces' => $pieces,
        ]);
    }

    public function togglePieceScrap($quantity): JsonResponse
    {
        $quantity = $quantity instanceof Quantity
            ? $quantity
            : Quantity::findOrFail($quantity);
        if ($quantity->isScrapped()) {
            $quantity->markAvailable();
        } else {
            $quantity->markScrapped();
        }

        $calculator = app(ProductPriceCalculator::class);
        $product = $calculator->syncProductAggregates($quantity->product->fresh());

        $statusEnum = $quantity->status ?? QuantityPieceStatus::Available;

        return response()->json([
            'success' => true,
            'piece_id' => $quantity->id,
            'status' => $statusEnum->value,
            'status_label' => $statusEnum->label(),
            'badge_class' => $statusEnum->badgeClass(),
            'is_scrapped' => $statusEnum === QuantityPieceStatus::Scrapped,
            'product_stock_quantity' => (int) $product->stock_quantity,
        ]);
    }
}
