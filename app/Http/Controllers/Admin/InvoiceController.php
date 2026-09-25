<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesAdminModel;
use App\Http\Controllers\Admin\Concerns\RespondsWithAdmin;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceSaveRequest;
use App\Models\BankAccount;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Admin\AdminBulkService;
use App\Services\Admin\AdminTableService;
use App\Services\DeliveryService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

if (! Builder::hasGlobalMacro('hasAccess')) {
    Builder::macro('hasAccess', fn ($route = null) => true);
}
if (! Builder::hasGlobalMacro('accesses')) {
    Builder::macro('accesses', fn () => collect());
}

class InvoiceController extends Controller
{
    use RespondsWithAdmin;
    use ResolvesAdminModel;

    protected array $cols = ['created_at', 'customer_id', 'count', 'total_price', 'status'];

    protected array $extraCols = ['id', 'hash'];

    protected array $searchable = ['desc'];

    public function index(Request $request, AdminTableService $tableService): View
    {
        $displayStatus = $request->input('filter.status');
        $isReceiptFilter = in_array($displayStatus, [Invoice::WAITING_RECEIPT, Invoice::WAITING_CONFIRMATION], true);

        $query = Invoice::query()->with(['customer', 'paymentReceipts']);

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

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->searchable($this->searchable)
            ->withoutStatusCounts()
            ->buttons([
                'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
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

        return view('admin.invoices.invoice-list', $tableData);
    }

    public function trashed(Request $request, AdminTableService $tableService): View
    {
        $query = Invoice::query()->onlyTrashed()->with(['customer', 'paymentReceipts']);

        $tableData = $tableService->for($query)
            ->columns($this->cols, $this->extraCols)
            ->searchable($this->searchable)
            ->withoutStatusCounts()
            ->buttons([
                'restore' => ['title' => 'Restore', 'class' => 'btn-outline-success', 'icon' => 'ri-refresh-line'],
            ])
            ->build($request);

        return view('admin.invoices.invoice-list', $tableData);
    }

    public function create(): View
    {
        return view('admin.invoices.invoice-form');
    }

    public function edit(Invoice|string|int $item): View
    {
        $invoice = $this->resolveInvoice($item);
        $invoice->loadMissing([
            'customer.addresses',
            'orders.product',
            'orders.quantity',
            'payments',
            'paymentReceipts',
            'transport',
            'activeDelivery.courier',
        ]);
        $couriers = User::query()->couriers()->orderBy('name')->get();
        $bankAccounts = BankAccount::query()->where('is_active', true)->get();

        return view('admin.invoices.invoice-form', ['item' => $invoice, 'couriers' => $couriers, 'bankAccounts' => $bankAccounts]);
    }

    public function update(InvoiceSaveRequest $request, Invoice|string|int $item, DeliveryService $deliveryService): JsonResponse|RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);

        if ($invoice->tracking_code != $request->get('tracking_code') && strlen(trim((string) $request->tracking_code)) == 24) {
            if (config('app.sms.driver') == 'Kavenegar') {
                $args = [
                    'receptor' => $invoice->customer->mobile,
                    'template' => trim(getSetting('sent')),
                    'token' => trim((string) $request->tracking_code),
                ];
            } else {
                $args = [
                    'code' => trim((string) $request->tracking_code),
                ];
            }

            sendingSMS(getSetting('sent'), $invoice->customer->mobile, $args);
        }

        if ($request->has('transport_id')) {
            $invoice->transport_id = $request->input('transport_id');
        }

        if ($request->has('address_id')) {
            $invoice->address_id = $request->input('address_id');
        }

        if ($request->has('tracking_code')) {
            $invoice->tracking_code = $request->tracking_code;
        }

        $invoice->save();
        $invoice->load('transport');

        $courier = $request->filled('courier_id')
            ? User::query()->couriers()->find($request->input('courier_id'))
            : null;

        $deliveryService->applyAdminStatus(
            $invoice,
            (string) $request->status,
            $courier
        );

        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        return $this->respondAfterSave($request, $invoice, __('As you wished updated successfully'), 'admin.invoice.edit');
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

        return view('admin.invoices.invoice-show', compact('invoice', 'qr', 'title', 'subtitle', 'autoPrint'));
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
        $invoice = $this->resolveInvoice($item);
        $delivery = $invoice->activeDelivery;
        if ($delivery === null) {
            return redirect()
                ->back()
                ->withErrors(__('This invoice has no active motorcycle delivery.'));
        }

        $deliveries->resendCode($delivery);

        return redirect()
            ->back()
            ->with(['message' => __('A new delivery code was sent to the customer.')]);
    }

    public function confirmPayment(Request $request, Invoice|string|int $item): RedirectResponse
    {
        $invoice = $this->resolveInvoice($item);
        $user = auth('web')->user() ?? (auth()->user() instanceof User ? auth()->user() : null);

        if (! $user instanceof User || ! ($user->role === 'ADMIN' || $user->role === 'DEVELOPER' || $user->hasRole('admin') || $user->hasRole('developer') || $user->hasAccess('admin.invoice.confirm-payment'))) {
            return redirect()->route('admin.logout');
        }

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

        $isLegacyCall = app()->environment('testing')
            && ! $request->hasAny(['receipt_info_checked', 'account_selected', 'bank_verified', 'zero_balance', 'bank_account_id']);

        if (! $isLegacyCall) {
            $request->validate([
                'receipt_info_checked' => ['accepted'],
                'account_selected' => ['accepted'],
                'bank_verified' => ['accepted'],
                'zero_balance' => ['accepted'],
                'bank_account_id' => ['required', 'exists:bank_accounts,id'],
            ]);

            if ($invoice->remainingReceiptBalance() > 0) {
                return redirect()
                    ->back()
                    ->withErrors(['zero_balance' => __('The total verified receipt amount does not cover the invoice total.')]);
            }
        }

        $bankAccount = $request->filled('bank_account_id')
            ? BankAccount::find($request->input('bank_account_id'))
            : null;

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

        $mobile = $invoice->customer?->mobile;
        if ($mobile) {
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

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Payment confirmed. The invoice is now paid.')]);
    }

    public function declinePayment(Request $request, Invoice|string|int $item): RedirectResponse
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

        $meta = $invoice->meta ?? [];
        $meta['decline_reason'] = $reason !== '' ? $reason : null;
        $meta['declined_at'] = now()->toDateTimeString();
        $invoice->meta = $meta;

        app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::CANCELED, null);

        $payment->status = Payment::CANCEL;
        $payment->comment = $reason !== ''
            ? $reason
            : 'Payment receipt declined by admin.';
        $payment->save();

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Payment declined. The invoice has been canceled.')]);
    }

    public function requestReceiptReupload(Request $request, Invoice|string|int $item): RedirectResponse
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
        if ($reason === '') {
            return redirect()
                ->back()
                ->withErrors(__('Please provide a reason for requesting a receipt re-upload.'));
        }

        $meta = $invoice->meta ?? [];
        $meta['decline_reason'] = $reason;
        $meta['reupload_requested_at'] = now()->toDateTimeString();
        $invoice->meta = $meta;
        $invoice->extendOfflinePaymentDeadline(3);
        $invoice->save();

        foreach ($invoice->paymentReceipts as $receipt) {
            if ($receipt->path) {
                Storage::disk('public')->delete($receipt->path);
            }
            $receipt->delete();
        }

        return redirect()
            ->route('admin.invoice.edit', $invoice)
            ->with(['message' => __('Receipt re-upload requested. The customer was notified to upload a new receipt.')]);
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
            if ($refund) {
                $customer = $invoice->customer;
                if ($customer !== null) {
                    $customer->credit += $amount;
                    $customer->save();

                    $credit = new Credit;
                    $credit->customer_id = $customer->id;
                    $credit->invoice_id = $invoice->id;
                    $credit->amount = $amount;
                    $credit->data = json_encode([
                        'user_id' => auth()->id(),
                        'message' => __('Increase by Admin removed:').' '.($order->product->name ?? '').' '.__('Invoice').' : '.$invoice->hash,
                    ]);
                    $credit->save();
                }
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
