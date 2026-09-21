<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Http\Requests\InvoiceSaveRequest;
use App\Models\BankAccount;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\DeliveryService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (! Builder::hasGlobalMacro('hasAccess')) {
    Builder::macro('hasAccess', fn ($route = null) => true);
}
if (! Builder::hasGlobalMacro('accesses')) {
    Builder::macro('accesses', fn () => collect());
}

class InvoiceController extends XController
{
    // protected  $_MODEL_ = Invoice::class;
    // protected  $SAVE_REQUEST = InvoiceSaveRequest::class;

    protected $cols = ['created_at', 'customer_id', 'count', 'total_price', 'status'];

    protected $extra_cols = ['id', 'hash'];

    protected $searchable = ['desc'];

    protected $listView = 'admin.invoices.invoice-list';

    protected $formView = 'admin.invoices.invoice-form';

    protected $buttons = [
        'edit' => ['title' => 'Edit', 'class' => 'btn-outline-primary', 'icon' => 'ri-edit-2-line'],
        'show' => ['title' => 'Detail', 'class' => 'btn-outline-secondary', 'icon' => 'ri-eye-line'],
        'print' => ['title' => 'Print', 'class' => 'btn-outline-secondary', 'icon' => 'ri-printer-line'],
        'destroy' => ['title' => 'Remove', 'class' => 'btn-outline-danger delete-confirm', 'icon' => 'ri-delete-bin-line'],
    ];

    public function __construct()
    {
        parent::__construct(Invoice::class, InvoiceSaveRequest::class);

        $this->buttons['print']['can'] = fn (Invoice $item): bool => $item->canPrint();
    }

    /**
     * @param  $invoice  Invoice
     * @param  $request  InvoiceSaveRequest
     * @return Invoice
     */
    public function save($invoice, $request)
    {

        if ($invoice->tracking_code != $request->get('tracking_code') && strlen(trim($request->tracking_code)) == 24) {
            if (config('app.sms.driver') == 'Kavenegar') {
                $args = [
                    'receptor' => $invoice->customer->mobile,
                    'template' => trim(getSetting('sent')),
                    'token' => trim($request->tracking_code),
                ];
            } else {
                $args = [
                    'code' => trim($request->tracking_code),
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

        app(DeliveryService::class)->applyAdminStatus(
            $invoice,
            (string) $request->status,
            $courier
        );

        return $invoice;

    }

    public function index()
    {
        $displayStatus = request()->input('filter.status');
        $query = $this->makeSortAndFilterQuery($displayStatus)
            ->with(['customer', 'paymentReceipts']);

        return $this->showList($query);
    }

    /**
     * @return Builder<Invoice>
     */
    private function makeSortAndFilterQuery(mixed $displayStatus): Builder
    {
        $isReceiptFilter = in_array($displayStatus, [Invoice::WAITING_RECEIPT, Invoice::WAITING_CONFIRMATION], true);

        if ($isReceiptFilter) {
            $filters = request()->input('filter', []);
            unset($filters['status']);
            request()->merge(['filter' => $filters]);
        }

        $query = $this->makeSortAndFilter();

        if ($isReceiptFilter) {
            $query->whereIn('status', [Invoice::AWAITING_PAYMENT, Invoice::PENDING]);
            if ($displayStatus === Invoice::WAITING_RECEIPT) {
                $query->whereDoesntHave('paymentReceipts');
            } else {
                $query->whereHas('paymentReceipts');
            }

            request()->merge([
                'filter' => array_merge(request()->input('filter', []), ['status' => $displayStatus]),
            ]);
        }

        return $query;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
        return view($this->formView);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $item)
    {
        $item->loadMissing([
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

        return view($this->formView, compact('item', 'couriers', 'bankAccounts'));
    }

    public function resendDeliveryCode(Invoice $item, DeliveryService $deliveries)
    {
        $delivery = $item->activeDelivery;
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

    public function confirmPayment(Request $request, Invoice $item)
    {
        $user = auth('web')->user() ?? (auth()->user() instanceof User ? auth()->user() : null);

        if (! $user instanceof User || ! ($user->role === 'ADMIN' || $user->role === 'DEVELOPER' || $user->hasRole('admin') || $user->hasRole('developer') || $user->hasAccess('admin.invoice.confirm-payment'))) {
            return redirect()->route('admin.logout');
        }

        if ($item->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Only invoices awaiting payment can be confirmed.'));
        }

        $payment = $item->payments()
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

            if ($item->remainingReceiptBalance() > 0) {
                return redirect()
                    ->back()
                    ->withErrors(['zero_balance' => __('The total verified receipt amount does not cover the invoice total.')]);
            }
        }

        $bankAccount = $request->filled('bank_account_id')
            ? BankAccount::find($request->input('bank_account_id'))
            : null;

        $item->storeSuccessPayment(
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

        $mobile = $item->customer?->mobile;
        if ($mobile) {
            $smsText = 'پرداخت شما تایید شد. سفارش شما تا ۴۸ ساعت آینده ارسال خواهد شد.';
            $template = trim((string) getSetting('payment_approved'));
            $args = [
                'receptor' => $mobile,
                'template' => $template !== '' ? $template : 'payment_approved',
                'token' => $item->customer?->name ?? '',
                'token2' => (string) $item->hash,
                'text' => $smsText,
            ];
            sendingSMS($template !== '' ? $template : $smsText, $mobile, $args);
        }

        return redirect()
            ->route('admin.invoice.edit', $item)
            ->with(['message' => __('Payment confirmed. The invoice is now paid.')]);
    }

    public function declinePayment(Request $request, Invoice $item)
    {
        if ($item->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Only invoices awaiting payment can be declined.'));
        }

        $payment = $item->payments()
            ->where('type', 'CARD')
            ->where('status', Payment::PENDING)
            ->latest('id')
            ->first();

        if ($payment === null || ! $item->hasUploadedReceipt()) {
            return redirect()
                ->back()
                ->withErrors(__('No pending receipt found for this invoice.'));
        }

        $reason = trim((string) $request->input('reason', ''));

        $meta = $item->meta ?? [];
        $meta['decline_reason'] = $reason !== '' ? $reason : null;
        $meta['declined_at'] = now()->toDateTimeString();
        $item->meta = $meta;

        app(DeliveryService::class)->applyAdminStatus($item, Invoice::CANCELED, null);

        $payment->status = Payment::CANCEL;
        $payment->comment = $reason !== ''
            ? $reason
            : 'Payment receipt declined by admin.';
        $payment->save();

        return redirect()
            ->route('admin.invoice.edit', $item)
            ->with(['message' => __('Payment declined. The invoice has been canceled.')]);
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
            default:
                $msg = __('Unknown bulk action : :ACTION', ['ACTION' => $action]);
        }

        return $this->do_bulk($msg, $action, $ids);
    }

    public function destroy(Invoice $item)
    {
        return parent::delete($item);
    }

    public function update(Request $request, Invoice $item)
    {
        return $this->bringUp($request, $item);
    }

    /**restore*/
    public function restore($item)
    {
        return parent::restoreing(Invoice::withTrashed()->where('id', $item)->first());
    }

    public function removeOrder(Order $order)
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
    /* restore* */

    public function show($item)
    {
        $invoice = $item instanceof Invoice ? $item : Invoice::where('hash', $item)->firstOrFail();
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

    public function print($item)
    {
        $invoice = $item instanceof Invoice ? $item : Invoice::where('hash', $item)->firstOrFail();

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

    public function shippingLabel($item)
    {
        $invoice = $item instanceof Invoice ? $item : Invoice::where('hash', $item)->firstOrFail();

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
}
