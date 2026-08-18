<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
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
     *     today: string|null
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
        ];
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
