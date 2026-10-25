<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()?->hasAnyAccess('invoice'), 403);

        $type = (string) $request->query('type', '');
        $supplier = (string) $request->query('supplier', '');
        $timeFrame = (string) $request->query('time_frame', 'all');
        $search = trim((string) $request->query('q', ''));
        $sort = (string) $request->query('sort', 'id');
        $sortType = strtolower((string) $request->query('sortType', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', config('app.panel.page_count', 15));
        if ($perPage < 5 || $perPage > 100) {
            $perPage = 15;
        }

        $baseSuccessQuery = Payment::query()
            ->where('status', Payment::SUCCESS)
            ->whereHas('invoice');
        $tz = 'Asia/Tehran';
        $now = now($tz);

        $quickRow = (clone $baseSuccessQuery)->selectRaw("
            COUNT(*) as all_count,
            COUNT(CASE WHEN json_extract(meta, '$.channel') = 'in_store' THEN 1 END) as physical_count,
            COUNT(CASE WHEN json_extract(meta, '$.channel') IS NULL OR json_extract(meta, '$.channel') != 'in_store' THEN 1 END) as online_count,
            COUNT(CASE WHEN supplier_id IS NOT NULL THEN 1 END) as supplier_count,
            COUNT(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 END) as today_count,
            COUNT(CASE WHEN created_at >= ? AND created_at <= ? THEN 1 END) as yesterday_count
        ", [
            $now->copy()->startOfDay()->utc()->toDateTimeString(),
            $now->copy()->endOfDay()->utc()->toDateTimeString(),
            $now->copy()->subDay()->startOfDay()->utc()->toDateTimeString(),
            $now->copy()->subDay()->endOfDay()->utc()->toDateTimeString(),
        ])->first();

        $quickCounts = [
            'all' => (int) ($quickRow?->all_count ?? 0),
            'physical' => (int) ($quickRow?->physical_count ?? 0),
            'online' => (int) ($quickRow?->online_count ?? 0),
            'supplier' => (int) ($quickRow?->supplier_count ?? 0),
            'today' => (int) ($quickRow?->today_count ?? 0),
            'yesterday' => (int) ($quickRow?->yesterday_count ?? 0),
        ];

        $query = (clone $baseSuccessQuery)->with([
            'invoice.customer',
            'supplier',
            'receipts',
        ]);

        $this->applyTypeFilter($query, $type);
        $this->applySupplierFilter($query, $supplier);
        $this->applyTimeFrameFilter($query, $timeFrame);
        $this->applySearch($query, $search);

        $kpisRow = (clone $query)->selectRaw("
            COUNT(*) as total_count,
            COALESCE(SUM(amount), 0) as total_amount,
            COUNT(CASE WHEN json_extract(meta, '$.channel') = 'in_store' THEN 1 END) as physical_count,
            COALESCE(SUM(CASE WHEN json_extract(meta, '$.channel') = 'in_store' THEN amount ELSE 0 END), 0) as physical_amount,
            COUNT(CASE WHEN json_extract(meta, '$.channel') IS NULL OR json_extract(meta, '$.channel') != 'in_store' THEN 1 END) as online_count,
            COALESCE(SUM(CASE WHEN json_extract(meta, '$.channel') IS NULL OR json_extract(meta, '$.channel') != 'in_store' THEN amount ELSE 0 END), 0) as online_amount,
            COUNT(CASE WHEN supplier_id IS NOT NULL THEN 1 END) as supplier_count,
            COALESCE(SUM(CASE WHEN supplier_id IS NOT NULL THEN amount ELSE 0 END), 0) as supplier_amount
        ")->first();

        $kpis = [
            'total_amount' => (int) ($kpisRow?->total_amount ?? 0),
            'total_count' => (int) ($kpisRow?->total_count ?? 0),
            'physical_amount' => (int) ($kpisRow?->physical_amount ?? 0),
            'physical_count' => (int) ($kpisRow?->physical_count ?? 0),
            'online_amount' => (int) ($kpisRow?->online_amount ?? 0),
            'online_count' => (int) ($kpisRow?->online_count ?? 0),
            'supplier_amount' => (int) ($kpisRow?->supplier_amount ?? 0),
            'supplier_count' => (int) ($kpisRow?->supplier_count ?? 0),
        ];

        if (in_array($sort, ['id', 'amount', 'created_at'], true)) {
            $query->orderBy($sort, $sortType);
        } else {
            $query->orderByDesc('id');
        }

        $items = $query->paginate($perPage)->withQueryString();
        $suppliers = Supplier::toSelectOptions();

        return view('admin.payments.payment-list', [
            'items' => $items,
            'suppliers' => $suppliers,
            'kpis' => $kpis,
            'quickCounts' => $quickCounts,
            'currentType' => $type,
            'currentSupplier' => $supplier,
            'currentTimeFrame' => $timeFrame,
            'search' => $search,
            'perPageOptions' => [15, 30, 50, 100],
        ]);
    }

    private function applyTypeFilter(Builder $query, string $type): void
    {
        if ($type === 'physical') {
            $query->physical();
        } elseif ($type === 'online') {
            $query->online();
        }
    }

    private function applySupplierFilter(Builder $query, string $supplier): void
    {
        if ($supplier === 'none') {
            $query->whereNull('supplier_id');
        } elseif ($supplier === 'any') {
            $query->whereNotNull('supplier_id');
        } elseif ($supplier !== '' && is_numeric($supplier)) {
            $query->where('supplier_id', (int) $supplier);
        }
    }

    private function applyTimeFrameFilter(Builder $query, string $timeFrame): void
    {
        $tz = 'Asia/Tehran';
        $now = now($tz);

        if ($timeFrame === 'today') {
            $query->whereBetween('created_at', [$now->copy()->startOfDay()->utc(), $now->copy()->endOfDay()->utc()]);
        } elseif ($timeFrame === 'yesterday') {
            $query->whereBetween('created_at', [$now->copy()->subDay()->startOfDay()->utc(), $now->copy()->subDay()->endOfDay()->utc()]);
        } elseif ($timeFrame === 'last_7_days') {
            $query->whereBetween('created_at', [$now->copy()->subDays(6)->startOfDay()->utc(), $now->copy()->endOfDay()->utc()]);
        } elseif ($timeFrame === 'last_30_days') {
            $query->whereBetween('created_at', [$now->copy()->subDays(29)->startOfDay()->utc(), $now->copy()->endOfDay()->utc()]);
        }
    }

    private function applySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('order_id', 'like', "%{$search}%")
                ->orWhere('reference_id', 'like', "%{$search}%")
                ->orWhere('comment', 'like', "%{$search}%")
                ->orWhereHas('invoice', fn (Builder $inv) => $inv->where('hash', 'like', "%{$search}%"))
                ->orWhereHas('invoice.customer', fn (Builder $c) => $c->where('name', 'like', "%{$search}%")->orWhere('mobile', 'like', "%{$search}%"))
                ->orWhereHas('supplier', fn (Builder $s) => $s
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%"));
        });
    }
}
