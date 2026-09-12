<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\XController;
use App\Http\Requests\InvoiceSaveRequest;
use App\Models\Credit;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\DeliveryService;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Invoice>
     */
    private function makeSortAndFilterQuery(mixed $displayStatus): \Illuminate\Database\Eloquent\Builder
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

        return view($this->formView, compact('item', 'couriers'));
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

    public function confirmPayment(Invoice $item)
    {
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

        $item->storeSuccessPayment(
            $payment->id,
            'CARD-CONFIRM-'.$payment->id.'-'.time()
        );

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

        $item->paymentReceipts()->delete();

        $meta = $item->meta ?? [];
        $meta['decline_reason'] = $reason !== '' ? $reason : null;
        $meta['declined_at'] = now()->toDateTimeString();
        $item->meta = $meta;
        $item->extendOfflinePaymentDeadline();
        $item->save();

        $payment->comment = $reason !== ''
            ? $reason
            : 'Payment receipt declined by admin.';
        $payment->save();

        return redirect()
            ->route('admin.invoice.edit', $item)
            ->with(['message' => __('Receipt declined. The customer can upload a new receipt.')]);
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
}
