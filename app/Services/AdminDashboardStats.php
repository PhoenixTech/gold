<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\Visitor;
use Illuminate\Support\Collection;

class AdminDashboardStats
{
    /**
     * @return array{
     *     rates: list<array{key: string, label: string, short: string, icon: string, value: int, updated_at: ?string}>,
     *     waitingReceipt: int,
     *     waitingConfirmation: int,
     *     needProcess: int,
     *     pendingTickets: int,
     *     products: int,
     *     goldProducts: int,
     *     silverProducts: int,
     *     customers: int,
     *     monthlySales: int,
     *     monthlyVisitors: int,
     *     bankAccount: ?BankAccount,
     *     recentInvoices: Collection<int, Invoice>,
     *     today: string|null,
     *     stockStats: array{total_count: int, total_weight: float, gold_count: int, gold_weight: float, silver_count: int, silver_weight: float},
     *     soldStats: array{total_count: int, total_weight: float, gold_count: int, gold_weight: float, silver_count: int, silver_weight: float}
     * }
     */
    public function data(): array
    {
        return [
            'rates' => $this->rates(),
            'waitingReceipt' => Invoice::query()->waitingReceipt()->count(),
            'waitingConfirmation' => Invoice::query()->waitingConfirmation()->count(),
            'needProcess' => Invoice::query()->needProcessing()->count(),
            'pendingTickets' => Ticket::query()->where('status', 'PENDING')->whereNull('parent_id')->count(),
            'products' => Product::query()->count(),
            'goldProducts' => Product::query()->where('metal_type', 'gold')->count(),
            'silverProducts' => Product::query()->where('metal_type', 'silver')->count(),
            'customers' => Customer::query()->count(),
            'monthlySales' => (int) Invoice::query()->soldThisMonth()->sum('total_price'),
            'monthlyVisitors' => Visitor::query()->where('created_at', '>=', now()->subMonth())->count(),
            'bankAccount' => BankAccount::activeAccount(),
            'recentInvoices' => Invoice::query()->with('customer')->latest('id')->limit(8)->get(),
            'today' => now()->ldate('Y/m/d'),
            'stockStats' => $this->stockStats(),
            'soldStats' => $this->soldStats(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $data = $this->data();

        return array_merge($data, [
            'marketSettings' => $this->marketSettings(),
            'salesSummary' => $this->salesSummary(),
            'recentSales' => Invoice::query()
                ->with('customer')
                ->whereIn('status', $this->successfulInvoiceStatuses())
                ->latest('id')
                ->limit(6)
                ->get(),
        ]);
    }

    /**
     * Calculate in-stock pieces and weight grouped by metal type.
     *
     * @return array{
     *     total_count: int,
     *     total_weight: float,
     *     gold_count: int,
     *     gold_weight: float,
     *     silver_count: int,
     *     silver_weight: float
     * }
     */
    public function stockStats(): array
    {
        $quantityStats = Quantity::query()
            ->join('products', 'quantities.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('quantities.count', '>', 0)
            ->selectRaw("
                LOWER(COALESCE(products.metal_type, 'gold')) as metal,
                SUM(quantities.count) as total_count,
                SUM(COALESCE(quantities.weight, 0) * quantities.count) as total_weight
            ")
            ->groupBy('metal')
            ->get()
            ->keyBy(fn ($row) => $row->metal === 'silver' ? 'silver' : 'gold');

        $productStats = Product::query()
            ->whereDoesntHave('quantities')
            ->where('stock_status', 'IN_STOCK')
            ->where('stock_quantity', '>', 0)
            ->selectRaw("
                LOWER(COALESCE(metal_type, 'gold')) as metal,
                SUM(stock_quantity) as total_count,
                SUM(COALESCE(weight, 0) * stock_quantity) as total_weight
            ")
            ->groupBy('metal')
            ->get()
            ->keyBy(fn ($row) => $row->metal === 'silver' ? 'silver' : 'gold');

        $goldCount = (int) ($quantityStats->get('gold')?->total_count ?? 0)
            + (int) ($productStats->get('gold')?->total_count ?? 0);
        $goldWeight = (float) ($quantityStats->get('gold')?->total_weight ?? 0)
            + (float) ($productStats->get('gold')?->total_weight ?? 0);

        $silverCount = (int) ($quantityStats->get('silver')?->total_count ?? 0)
            + (int) ($productStats->get('silver')?->total_count ?? 0);
        $silverWeight = (float) ($quantityStats->get('silver')?->total_weight ?? 0)
            + (float) ($productStats->get('silver')?->total_weight ?? 0);

        return [
            'total_count' => $goldCount + $silverCount,
            'total_weight' => round($goldWeight + $silverWeight, 3),
            'gold_count' => $goldCount,
            'gold_weight' => round($goldWeight, 3),
            'silver_count' => $silverCount,
            'silver_weight' => round($silverWeight, 3),
        ];
    }

    /**
     * Calculate sold pieces and weight grouped by metal type.
     *
     * @return array{
     *     total_count: int,
     *     total_weight: float,
     *     gold_count: int,
     *     gold_weight: float,
     *     silver_count: int,
     *     silver_weight: float
     * }
     */
    public function soldStats(): array
    {
        $quantityStats = Quantity::query()
            ->join('products', 'quantities.product_id', '=', 'products.id')
            ->whereNull('products.deleted_at')
            ->where('quantities.count', '<=', 0)
            ->selectRaw("
                LOWER(COALESCE(products.metal_type, 'gold')) as metal,
                COUNT(quantities.id) as total_count,
                SUM(COALESCE(quantities.weight, 0)) as total_weight
            ")
            ->groupBy('metal')
            ->get()
            ->keyBy(fn ($row) => $row->metal === 'silver' ? 'silver' : 'gold');

        $productStats = Product::query()
            ->whereDoesntHave('quantities')
            ->where('sell', '>', 0)
            ->selectRaw("
                LOWER(COALESCE(metal_type, 'gold')) as metal,
                SUM(sell) as total_count,
                SUM(COALESCE(weight, 0) * sell) as total_weight
            ")
            ->groupBy('metal')
            ->get()
            ->keyBy(fn ($row) => $row->metal === 'silver' ? 'silver' : 'gold');

        $goldCount = (int) ($quantityStats->get('gold')?->total_count ?? 0)
            + (int) ($productStats->get('gold')?->total_count ?? 0);
        $goldWeight = (float) ($quantityStats->get('gold')?->total_weight ?? 0)
            + (float) ($productStats->get('gold')?->total_weight ?? 0);

        $silverCount = (int) ($quantityStats->get('silver')?->total_count ?? 0)
            + (int) ($productStats->get('silver')?->total_count ?? 0);
        $silverWeight = (float) ($quantityStats->get('silver')?->total_weight ?? 0)
            + (float) ($productStats->get('silver')?->total_weight ?? 0);

        return [
            'total_count' => $goldCount + $silverCount,
            'total_weight' => round($goldWeight + $silverWeight, 3),
            'gold_count' => $goldCount,
            'gold_weight' => round($goldWeight, 3),
            'silver_count' => $silverCount,
            'silver_weight' => round($silverWeight, 3),
        ];
    }

    /**
     * @return list<array{key: string, label: string, icon: string, value: string, suffix: string, updated_at: ?string}>
     */
    private function marketSettings(): array
    {
        $settings = Setting::query()
            ->whereIn('key', [
                'gold',
                'gold24',
                'silver',
                'dollar',
                'min',
                'offline_payment_hours',
                'cart_quote_minutes',
            ])
            ->get()
            ->keyBy('key');

        $items = [
            'gold' => [
                'label' => __('Gold 18K Price'),
                'icon' => 'ri-coins-line',
                'suffix' => config('app.currency.symbol'),
            ],
            'gold24' => [
                'label' => __('Gold 24K Price'),
                'icon' => 'ri-vip-diamond-line',
                'suffix' => config('app.currency.symbol'),
            ],
            'silver' => [
                'label' => __('Silver price'),
                'icon' => 'ri-vip-diamond-line',
                'suffix' => config('app.currency.symbol'),
            ],
            'dollar' => [
                'label' => __('Dollar Rate'),
                'icon' => 'ri-money-dollar-circle-line',
                'suffix' => config('app.currency.symbol'),
            ],
            'min' => [
                'label' => __('Minimum percent'),
                'icon' => 'ri-percent-line',
                'suffix' => '%',
            ],
            'offline_payment_hours' => [
                'label' => __('Offline payment deadline'),
                'icon' => 'ri-time-line',
                'suffix' => __('hours'),
            ],
            'cart_quote_minutes' => [
                'label' => __('Cart quote duration'),
                'icon' => 'ri-timer-line',
                'suffix' => __('minutes'),
            ],
        ];

        $marketSettings = [];
        foreach ($items as $key => $meta) {
            $setting = $settings->get($key);
            $value = $setting?->raw ?: $setting?->value;

            $marketSettings[] = [
                'key' => $key,
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'value' => (string) ($value ?? 0),
                'suffix' => $meta['suffix'],
                'updated_at' => $setting?->updated_at
                    ? Invoice::formatPersianDateTime($setting->updated_at)
                    : null,
            ];
        }

        return $marketSettings;
    }

    /**
     * @return array{total_price: int, invoice_count: int, item_count: int, total_weight: float}
     */
    private function salesSummary(): array
    {
        $successfulInvoices = Invoice::query()->whereIn('status', $this->successfulInvoiceStatuses());
        $totalWeight = 0.0;

        $soldOrders = Order::query()
            ->with([
                'product:id,weight',
                'quantity:id,weight',
            ])
            ->whereHas('invoice', function ($query): void {
                $query->whereIn('status', $this->successfulInvoiceStatuses());
            })
            ->get(['id', 'product_id', 'quantity_id', 'count']);

        foreach ($soldOrders as $order) {
            $weight = $order->quantity?->weight ?? $order->product?->weight ?? 0;
            $totalWeight += (float) $weight * (int) ($order->count ?: 1);
        }

        return [
            'total_price' => (int) (clone $successfulInvoices)->sum('total_price'),
            'invoice_count' => (int) (clone $successfulInvoices)->count(),
            'item_count' => (int) (clone $successfulInvoices)->sum('count'),
            'total_weight' => round($totalWeight, 3),
        ];
    }

    /**
     * @return list<string>
     */
    private function successfulInvoiceStatuses(): array
    {
        return [
            Invoice::PAID,
            Invoice::PROCESSING,
            Invoice::COMPLETED,
        ];
    }

    /**
     * Format a weight in grams cleanly without trailing zeroes.
     */
    public static function formatWeight(float|int|string|null $weight): string
    {
        if ($weight === null || $weight === '') {
            return '0';
        }

        $num = (float) $weight;
        if ($num == 0.0) {
            return '0';
        }

        return rtrim(rtrim(number_format($num, 3, '.', ''), '0'), '.');
    }

    /**
     * @return list<array{key: string, label: string, short: string, icon: string, value: int, updated_at: ?string}>
     */
    private function rates(): array
    {
        $settings = Setting::query()
            ->whereIn('key', ['gold', 'silver', 'dollar'])
            ->get()
            ->keyBy('key');

        $items = [
            'gold' => [
                'label' => __('Gold 18K Price'),
                'short' => '18K',
                'icon' => 'ri-coins-line',
            ],
            'silver' => [
                'label' => __('Silver price'),
                'short' => __('Silver'),
                'icon' => 'ri-vip-diamond-line',
            ],
            'dollar' => [
                'label' => __('Dollar Rate'),
                'short' => '$',
                'icon' => 'ri-money-dollar-circle-line',
            ],
        ];

        $rates = [];
        foreach ($items as $key => $meta) {
            $setting = $settings->get($key);
            $rates[] = [
                'key' => $key,
                'label' => $meta['label'],
                'short' => $meta['short'],
                'icon' => $meta['icon'],
                'value' => (int) ($setting?->raw ?: $setting?->value ?: 0),
                'updated_at' => $setting?->updated_at
                    ? Invoice::formatPersianDateTime($setting->updated_at)
                    : null,
            ];
        }

        return $rates;
    }
}
