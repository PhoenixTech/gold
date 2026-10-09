<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddInvoicePaymentRequest;
use App\Http\Requests\CancelInvoiceRequest;
use App\Http\Requests\ConfirmInvoicePaymentRequest;
use App\Http\Requests\DeclineInvoicePaymentRequest;
use App\Http\Requests\InvoiceSaveRequest;
use App\Http\Requests\RequestReceiptReuploadRequest;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ManualInvoiceService;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\CreditService;
use App\Services\DeliveryService;
use App\Services\InvoiceCancellationService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

if (! Builder::hasGlobalMacro('hasAccess')) {
    Builder::macro('hasAccess', fn ($route = null) => true);
}
if (! Builder::hasGlobalMacro('accesses')) {
    Builder::macro('accesses', fn () => collect());
}

class InvoiceController extends Controller
{
    use ResolvesAdminModel;
    use RespondsWithAdmin;

    /**
     * Traditional فاکتور columns: invoice number, date, customer, phone,
     * items/weight, amount, payment, delivery, status.
     *
     * Several of these are computed rather than real columns, so the query
     * carries withCount/withSum/selectSub aliases and the table service selects
     * '*' instead of the column list.
     *
     * @var list<string>
     */
    protected array $cols = [
        'hash',
        'created_at',
        'customer_id',
        'customer_mobile',
        'items_summary',
        'total_price',
        'payment_progress',
        'delivery_method',
        'status',
    ];

    protected array $extraCols = ['id', 'hash'];

    protected array $searchable = ['hash', 'desc'];

    public function index(Request $request, AdminTableService $tableService): View
    {
        $displayStatus = $request->input('filter.status');
        $isReceiptFilter = in_array($displayStatus, [Invoice::WAITING_RECEIPT, Invoice::WAITING_CONFIRMATION], true);
        $deliveryFilter = $request->input('filter.delivery_type');

        // withCount (rather than with) keeps hasUploadedReceipt() off the N+1
        // path while avoiding loading every receipt row for every listed order.
        //
        // NOTE: addSelect(), never select(). Eloquent's select() resets the
        // column list, which silently drops the withCount/withSum subqueries
        // that were registered before it.
        $query = Invoice::query()
            ->with(['customer', 'transport', 'activeDelivery.courier'])
            ->withCount('paymentReceipts')
            ->withSum('paymentReceipts as receipts_amount', 'amount')
            ->withSum(['payments as shop_payments_amount' => fn (Builder $payment) => $payment->inStore()], 'amount')
            ->addSelect('invoices.*')
            ->addSelect([
                'total_weight' => Order::query()
                    ->selectRaw('COALESCE(SUM(quantities.weight), 0)')
                    ->join('quantities', 'quantities.id', '=', 'orders.quantity_id')
                    ->whereColumn('orders.invoice_id', 'invoices.id'),
            ]);

        if ($isReceiptFilter) {
            $filters = (array) $request->input('filter', []);
            unset($filters['status']);
            $request->merge(['filter' => $filters]);

            $query->whereIn('status', [Invoice::AWAITING_PAYMENT, Invoice::PENDING]);
            if ($displayStatus === Invoice::WAITING_RECEIPT) {
                $query->whereDoesntHave('paymentReceipts');
            } else {
                $query->whereHas('paymentReceipts');
            }
        }

        if (in_array($deliveryFilter, ['address', 'pickup'], true)) {
            $query->where('delivery_type', $deliveryFilter);
        } else {
            // Not a real column: strip it so applyFilters() never sees it.
            $filters = (array) $request->input('filter', []);
            unset($filters['delivery_type']);
            $request->merge(['filter' => $filters]);
        }

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->selectColumns(['*'])
            ->searchable($this->searchable)
            ->searchableRelations([
                'customer' => ['name', 'mobile', 'code'],
            ])
            ->perPage($this->perPage($request))
            ->columnLabels($this->columnLabels())
            ->withCustomSort(fn (Builder $q, ?string $sort, string $sortType) => $this->sortInvoices($q, $sort, $sortType))
            ->withQuickCounts([
                'waiting_receipt' => fn () => Invoice::waitingReceipt()->count(),
                'waiting_confirmation' => fn () => Invoice::waitingConfirmation()->count(),
                'paid' => fn () => Invoice::query()->where('status', Invoice::PAID)->count(),
                'processing' => fn () => Invoice::query()->whereIn('status', [Invoice::PROCESSING, Invoice::READY_FOR_PICKUP])->count(),
                'out_for_delivery' => fn () => Invoice::query()->where('status', Invoice::OUT_FOR_DELIVERY)->count(),
                'completed' => fn () => Invoice::query()->where('status', Invoice::COMPLETED)->count(),
                'closed' => fn () => Invoice::query()->whereIn('status', [Invoice::CANCELED, Invoice::FAILED])->count(),
            ])
            ->buttons([
                'edit' => [
                    'title' => 'Edit',
                    'class' => 'btn-outline-primary',
                    'icon' => 'ri-edit-2-line',
                    'can' => fn (Invoice $item): bool => $item->status !== Invoice::COMPLETED,
                ],
                'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
                'print' => [
                    'title' => 'Print',
                    'class' => 'btn-outline-secondary',
                    'icon' => 'ri-printer-line',
                    'can' => fn (Invoice $item): bool => $item->canPrint(),
                ],
                'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
            ])
            ->build($request);

        if ($isReceiptFilter) {
            $request->merge([
                'filter' => array_merge((array) $request->input('filter', []), ['status' => $displayStatus]),
            ]);
        }

        $tableData['perPageOptions'] = [15, 30, 50, 100];
        $tableData['listTotals'] = $this->listTotals($tableData['items']);
        $tableData['statusChips'] = $this->statusChips($tableData['quickCounts']);

        return view('admin.invoices.invoice-list', $tableData);
    }

    private function joinCustomersAndOrder(Builder $query, string $column, string $sortType): bool
    {
        $query->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->orderBy($column, $sortType);

        return true;
    }

    /**
     * Header labels for columns whose names are internal identifiers rather than
     * something worth showing an admin.
     *
     * @return array<string, string>
     */
    private function columnLabels(): array
    {
        return [
            'hash' => __('Invoice number'),
            'customer_id' => __('Customer'),
            'customer_mobile' => __('Phone number'),
            'items_summary' => __('Items / weight'),
            'payment_progress' => __('Payment'),
            'delivery_method' => __('Fulfillment'),
        ];
    }

    /**
     * Sorting a rendered cell means ordering by whatever it actually
     * represents. `receipts_amount`, `total_weight` and `payment_receipts_count`
     * are subquery aliases in the select list, so ORDER BY <alias> is valid SQL;
     * the customer columns need a join.
     */
    private function sortInvoices(Builder $query, ?string $sort, string $sortType): bool
    {
        return match ($sort) {
            'customer_id' => $this->joinCustomersAndOrder($query, 'customers.name', $sortType),
            'customer_mobile' => $this->joinCustomersAndOrder($query, 'customers.mobile', $sortType),
            'customer_code' => $this->joinCustomersAndOrder($query, 'customers.code', $sortType),
            'payment_progress' => (bool) $query->orderBy('receipts_amount', $sortType),
            'items_summary' => (bool) $query->orderBy('invoices.count', $sortType)
                ->orderBy('total_weight', $sortType),
            'delivery_method' => (bool) $query->orderBy('invoices.delivery_type', $sortType),
            'total_weight' => (bool) $query->orderBy('total_weight', $sortType),
            default => false,
        };
    }

    /**
     * Status quick-filter chips with their live counts.
     *
     * @param  array<string, int>  $counts
     * @return list<array{key: string, label: string, count: int, icon: string}>
     */
    private function statusChips(array $counts): array
    {
        return [
            ['key' => Invoice::WAITING_RECEIPT, 'label' => __('Waiting receipt'), 'count' => (int) ($counts['waiting_receipt'] ?? 0), 'icon' => 'ri-timer-line'],
            ['key' => Invoice::WAITING_CONFIRMATION, 'label' => __('Review receipt'), 'count' => (int) ($counts['waiting_confirmation'] ?? 0), 'icon' => 'ri-file-list-3-line'],
            ['key' => Invoice::PAID, 'label' => __('Paid'), 'count' => (int) ($counts['paid'] ?? 0), 'icon' => 'ri-checkbox-circle-line'],
            ['key' => Invoice::PROCESSING, 'label' => __('Processing'), 'count' => (int) ($counts['processing'] ?? 0), 'icon' => 'ri-package-line'],
            ['key' => Invoice::OUT_FOR_DELIVERY, 'label' => __('Out for delivery'), 'count' => (int) ($counts['out_for_delivery'] ?? 0), 'icon' => 'ri-motorbike-line'],
            ['key' => Invoice::COMPLETED, 'label' => __('Completed'), 'count' => (int) ($counts['completed'] ?? 0), 'icon' => 'ri-check-double-line'],
        ];
    }

    /**
     * Summary of the rows on the current page, plus the filtered page total.
     */
    private function listTotals(LengthAwarePaginator $items): array
    {
        return [
            'rows' => $items->count(),
            'total_count' => $items->total(),
            'page_total' => (int) $items->getCollection()->sum('total_price'),
            'page_weight' => (float) $items->getCollection()->sum('total_weight'),
            'currency' => config('app.currency.symbol') ?: __('Toman'),
        ];
    }

    private function perPage(Request $request): int
    {
        $requested = (int) $request->input('per_page', 0);
        $default = (int) config('app.panel.page_count', 15);

        return $requested > 0 ? min(200, $requested) : $default;
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $query = Invoice::query()->onlyTrashed()->with(['customer', 'paymentReceipts']);

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->selectColumns(['*'])
            ->searchable($this->searchable)
            ->searchableRelations([
                'customer' => ['name', 'mobile', 'code'],
            ])
            ->perPage($this->perPage($request))
            ->columnLabels($this->columnLabels())
            ->withCustomSort(fn (Builder $q, ?string $sort, string $sortType) => $this->sortInvoices($q, $sort, $sortType))
            ->withoutStatusCounts()
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        $tableData['perPageOptions'] = [15, 30, 50, 100];
        $tableData['listTotals'] = null;
        $tableData['statusChips'] = [];

        return view('admin.invoices.invoice-list', $tableData);
    }

    public function edit(Invoice|string|int $item): View|RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->status === Invoice::COMPLETED) {
            return redirect()
                ->route('admin.invoice.show', $invoice)
                ->with(['message' => __('Completed invoices cannot be edited.')]);
        }

        $invoice->loadMissing([
            'customer.addresses',
            'orders.product',
            'orders.quantity',
            'payments.supplier',
            'payments.receipts',
            'paymentReceipts',
            'transport',
            'activeDelivery.courier',
            'createdBy',
        ]);
        $couriers = User::query()->couriers()->orderBy('name')->get();
        $bankAccounts = BankAccount::query()->where('is_active', true)->get();
        $suppliers = Supplier::query()->orderBy('last_name')->orderBy('first_name')->get();

        return view('admin.invoices.invoice-form', [
            'item' => $invoice,
            'couriers' => $couriers,
            'bankAccounts' => $bankAccounts,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(InvoiceSaveRequest $request, Invoice|string|int $item, DeliveryService $deliveryService): JsonResponse|RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->status === Invoice::COMPLETED) {
            return redirect()
                ->route('admin.invoice.show', $invoice)
                ->withErrors(__('Completed invoices cannot be edited.'));
        }

        $courier = $request->filled('courier_id')
            ? User::query()->couriers()->find($request->input('courier_id'))
            : null;

        $deliveryService->applyAdminStatus($invoice, (string) $request->status, $courier);

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        $targetRoute = $invoice->fresh()->status === Invoice::COMPLETED ? 'admin.invoice.show' : 'admin.invoice.edit';

        return $this->respondAfterSave($request, $invoice, __('As you wished updated successfully'), $targetRoute);
    }

    public function destroy(Invoice|string|int $item): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);
        logAdmin(__METHOD__, Invoice::class, $invoice->id);
        $invoice->delete();

        return redirect()->back()->with(['message' => __('As you wished removed successfully')]);
    }

    public function restore(Invoice|string|int $item): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item, true);
        logAdmin(__METHOD__, Invoice::class, $invoice->id);
        $invoice->restore();

        return redirect()->back()->with(['message' => __('As you wished restored successfully')]);
    }

    public function bulk(Request $request, AdminBulkService $bulkService): RedirectResponse
    {
        return $bulkService->handle(Invoice::class, $request->input('action'), (array) $request->input('id', []));
    }

    public function show(Invoice|string|int $item): View
    {
        $invoice = $this->resolveInvoice($item);
        $invoice->loadMissing([
            'customer.addresses.state',
            'customer.addresses.city',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'payments.receipts',
            'paymentReceipts',
            'transport',
            'activeDelivery.courier',
        ]);

        $title = __('Invoice').' #'.$invoice->hash;
        $subtitle = __('Invoice ID:').' '.$invoice->hash;

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);
        $qr = new QRCode($options);

        $autoPrint = request()->boolean('print', false);

        return view('admin.invoices.invoice-show', compact('invoice', 'qr', 'title', 'subtitle', 'autoPrint'));
    }

    public function print(Invoice|string|int $item): View|RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if (! $invoice->canPrint()) {
            return redirect()
                ->route('admin.invoice.index')
                ->withErrors(__('Only accepted invoices in the fulfillment pipeline can be printed.'));
        }

        $invoice->loadMissing([
            'customer.addresses.state',
            'customer.addresses.city',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'payments.receipts',
            'paymentReceipts',
            'transport',
            'activeDelivery.courier',
        ]);

        $title = __('Print invoice').' - '.$invoice->hash;
        $subtitle = __('Invoice ID:').' '.$invoice->hash;

        $options = new QROptions([
            'version' => 5,
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
        ]);
        $qr = new QRCode($options);

        $autoPrint = true;

        return view('admin.invoices.invoice-print', compact('invoice', 'qr', 'title', 'subtitle', 'autoPrint'));
    }

    public function shippingLabel(Invoice|string|int $item): View
    {
        $invoice = $this->resolveInvoice($item);

        $invoice->loadMissing([
            'customer.addresses.state',
            'customer.addresses.city',
            'address.state',
            'address.city',
            'orders.product',
            'orders.quantity',
            'transport',
            'activeDelivery.courier',
        ]);

        $title = __('Shipping Label').' - '.$invoice->hash;

        return view('admin.invoices.shipping-label', compact('invoice', 'title'));
    }

    public function resendDeliveryCode(Invoice|string|int $item, DeliveryService $deliveries): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyAccess('invoice'), 403);

        $invoice = $this->resolveInvoice($item);
        $delivery = $invoice->activeDelivery;
        if ($delivery === null) {
            return redirect()
                ->back()
                ->withErrors(__('This invoice has no active motorcycle delivery.'));
        }

        try {
            $deliveries->resendCode($delivery);
        } catch (ValidationException $exception) {
            // A locked or already-closed delivery used to bubble a raw
            // ValidationException straight to the error page.
            return redirect()
                ->back()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->back()
            ->with(['message' => __('A new delivery code was sent to the customer.')]);
    }

    public function confirmPayment(ConfirmInvoicePaymentRequest $request, Invoice|string|int $item): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Only invoices awaiting payment can be confirmed.'));
        }

        $payment = $invoice->payments()
            ->where('type', 'CARD')
            ->where('status', Payment::PENDING)
            ->latest('id')
            ->first();

        if ($payment === null) {
            return redirect()
                ->back()
                ->withErrors(__('No pending card payment found for this invoice.'));
        }

        if ($invoice->remainingReceiptBalance() > 0) {
            return redirect()
                ->back()
                ->withErrors(['zero_balance' => __('The total verified receipt amount does not cover the invoice total.')]);
        }

        $bankAccount = BankAccount::find($request->input('bank_account_id'));

        DB::transaction(function () use ($invoice, $payment, $bankAccount): void {
            $invoice->storeSuccessPayment(
                $payment->id,
                'CARD-CONFIRM-'.$payment->id.'-'.time()
            );

            $payment->refresh();
            $meta = $payment->meta ?? [];
            $meta['confirmed_by'] = auth()->id();
            $meta['confirmed_at'] = now()->toDateTimeString();
            if ($bankAccount) {
                $meta['bank_account_id'] = $bankAccount->id;
                $meta['bank_account_name'] = $bankAccount->bank_name;
            }
            $payment->meta = $meta;
            $payment->save();
        });

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        $this->notifyPaymentApproved($invoice);

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Payment confirmed. The invoice is now paid.')]);
    }

    public function addPayment(AddInvoicePaymentRequest $request, Invoice|string|int $item, ManualInvoiceService $sales): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);
        $data = $request->validated();
        $payment = $sales->addPaymentToInvoice($invoice, $data, $request->user(), $request->file('slip'));

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Payment of :amount Toman was recorded.', ['amount' => number_format($payment->amount)])]);
    }

    public function declinePayment(DeclineInvoicePaymentRequest $request, Invoice|string|int $item, DeliveryService $deliveryService): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Only invoices awaiting payment can be declined.'));
        }

        $payment = $invoice->payments()
            ->where('type', 'CARD')
            ->where('status', Payment::PENDING)
            ->latest('id')
            ->first();

        if ($payment === null || ! $invoice->hasUploadedReceipt()) {
            return redirect()
                ->back()
                ->withErrors(__('No pending receipt found for this invoice.'));
        }

        $reason = trim((string) $request->input('reason', ''));

        DB::transaction(function () use ($invoice, $payment, $reason, $deliveryService): void {
            $meta = $invoice->meta ?? [];
            $meta['decline_reason'] = $reason !== '' ? $reason : null;
            $meta['declined_at'] = now()->toDateTimeString();
            $invoice->meta = $meta;
            $invoice->save();

            $deliveryService->applyAdminStatus($invoice, Invoice::CANCELED, null);

            $payment->status = Payment::CANCEL;
            $payment->comment = $reason !== ''
                ? $reason
                : 'Payment receipt declined by admin.';
            $payment->save();
        });

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        $this->notifyPaymentDeclined($invoice, $reason);

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Payment declined. The invoice has been canceled.')]);
    }

    public function requestReceiptReupload(RequestReceiptReuploadRequest $request, Invoice|string|int $item): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Only invoices awaiting payment can have receipt re-upload requested.'));
        }

        $payment = $invoice->payments()
            ->where('type', 'CARD')
            ->where('status', Payment::PENDING)
            ->latest('id')
            ->first();

        if ($payment === null || ! $invoice->hasUploadedReceipt()) {
            return redirect()
                ->back()
                ->withErrors(__('No pending receipt found for this invoice.'));
        }

        $reason = trim((string) $request->input('reason', ''));

        // Collect the paths inside the transaction, but only unlink once the
        // database write has committed -- otherwise a mid-transaction failure
        // destroys the customer's evidence with nothing to show for it.
        $receiptPaths = DB::transaction(function () use ($invoice, $reason): array {
            $meta = $invoice->meta ?? [];
            $meta['reupload_reason'] = $reason;
            $meta['reupload_requested_at'] = now()->toDateTimeString();
            $invoice->meta = $meta;
            $invoice->extendOfflinePaymentDeadline(3);
            $invoice->save();

            $paths = $invoice->paymentReceipts->pluck('path')->filter()->all();

            $invoice->paymentReceipts()->delete();

            return $paths;
        });

        if ($receiptPaths !== []) {
            Storage::disk('public')->delete($receiptPaths);
        }

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        $this->notifyPaymentDeclined($invoice, $reason);

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Receipt re-upload requested. The customer was notified to upload a new receipt.')]);
    }

    public function cancel(CancelInvoiceRequest $request, Invoice|string|int $item, InvoiceCancellationService $cancellation): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        try {
            $refunded = $cancellation->cancel($invoice, trim((string) $request->input('reason')), auth()->user());
        } catch (ValidationException $exception) {
            return redirect()->back()->withErrors($exception->errors());
        }

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        $this->notifyCanceled($invoice, $refunded);

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => $refunded > 0
                ? __('Invoice canceled and :amount was returned to the customer credit.', ['amount' => number_format($refunded)])
                : __('Invoice canceled.')]);
    }

    private function notifyCanceled(Invoice $invoice, int $refunded): void
    {
        $mobile = $invoice->customer?->mobile;
        if (! $mobile) {
            return;
        }

        $text = $refunded > 0
            ? __('Your order :hash was canceled and :amount was added to your credit.', ['hash' => $invoice->hash, 'amount' => number_format($refunded)])
            : __('Your order :hash was canceled.', ['hash' => $invoice->hash]);

        sendingSMS($text, $mobile, ['receptor' => $mobile, 'text' => $text]);
    }

    private function notifyPaymentApproved(Invoice $invoice): void
    {
        $mobile = $invoice->customer?->mobile;
        if (! $mobile) {
            return;
        }

        $smsText = 'پرداخت شما تایید شد. سفارش شما تا ۴۸ ساعت آینده ارسال خواهد شد.';
        $template = trim((string) getSetting('payment_approved'));
        $args = [
            'receptor' => $mobile,
            'template' => $template !== '' ? $template : 'payment_approved',
            'token' => $invoice->customer?->name ?? '',
            'token2' => (string) $invoice->hash,
            'text' => $smsText,
        ];

        sendingSMS($template !== '' ? $template : $smsText, $mobile, $args);
    }

    private function notifyPaymentDeclined(Invoice $invoice, string $reason): void
    {
        $mobile = $invoice->customer?->mobile;
        if (! $mobile) {
            return;
        }

        $template = trim((string) getSetting('receipt_declined'));

        if ($template !== '') {
            sendingSMS($template, $mobile, [
                'receptor' => $mobile,
                'template' => $template,
                'token' => $invoice->customer?->name ?? '',
                'token2' => (string) $invoice->hash,
                'reason' => $reason,
                'text' => $reason,
            ]);

            return;
        }

        sendingSMS($reason, $mobile, [
            'receptor' => $mobile,
            'token' => $invoice->customer?->name ?? '',
            'token2' => (string) $invoice->hash,
            'reason' => $reason,
            'text' => $reason,
        ]);
    }

    public function removeOrder(Order $order): RedirectResponse
    {
        $invoice = $order->invoice;

        if ($invoice === null) {
            return redirect()->back()->withErrors(__('Order not found.'));
        }

        $amount = (int) $order->price_total;
        $refund = $amount > 0
            && in_array($invoice->status, Invoice::successfulStatuses(), true);

        DB::transaction(function () use ($order, $invoice, $amount, $refund): void {
            if ($refund && $invoice->customer !== null) {
                app(CreditService::class)->refund(
                    $invoice->customer,
                    $amount,
                    $invoice,
                    __('Increase by Admin removed:').' '.($order->product->name ?? '').' '.__('Invoice').' : '.$invoice->hash,
                    auth()->user()
                );
            }

            $invoice->releaseReservedStockFor($order);
            $order->delete();
            $invoice->recalculateTotals();

            if (! $invoice->orders()->exists()) {
                app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::CANCELED, null);
            }
        });

        return redirect()->back()->with('message', $refund
            ? __('Order removed and the amount was returned to the customer credit.')
            : __('Order removed successfully'));
    }

    protected function resolveInvoice(Invoice|string|int $item, bool $withTrashed = false): Invoice
    {
        return $this->resolveModel(Invoice::class, $item, $withTrashed);
    }
}
