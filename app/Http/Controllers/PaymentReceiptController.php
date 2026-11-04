<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentReceiptsRequest;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;

class PaymentReceiptController extends Controller
{
    public function showReceiptForm(Invoice $invoice)
    {
        $customer = auth('customer')->user();

        if ($customer === null || $invoice->customer_id !== $customer->id) {
            abort(403);
        }

        if ($invoice->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()->route('client.invoice', $invoice);
        }

        $title = __('Register Payment Receipt');
        $subtitle = __('Invoice ID:').' '.$invoice->hash;

        $invoice->loadMissing([
            'customer',
            'payments',
            'paymentReceipts',
        ]);

        return view('client.customer.receipt', compact('title', 'subtitle', 'invoice'));
    }

    public function store(StorePaymentReceiptsRequest $request, Invoice $invoice)
    {
        $customer = auth('customer')->user();

        if ($customer === null || $invoice->customer_id !== $customer->id) {
            abort(403);
        }

        if ($invoice->status !== Invoice::AWAITING_PAYMENT) {
            return redirect()
                ->back()
                ->withErrors(__('Receipts can only be uploaded while the invoice is awaiting payment.'));
        }

        if ($invoice->isOfflinePaymentExpired()) {
            return redirect()
                ->back()
                ->withErrors(__('The offline payment deadline for this invoice has passed. The invoice was failed, please contact support.'));
        }

        $payment = $invoice->cardPayment();

        if ($payment === null || $payment->status !== Payment::PENDING) {
            return redirect()
                ->back()
                ->withErrors(__('No pending card payment found for this invoice.'));
        }

        $activeAccountId = BankAccount::activeAccount()?->id;

        foreach ($request->validated('receipts') as $index => $item) {
            $file = $request->file("receipts.{$index}.slip");

            PaymentReceipt::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'path' => $file->store('payment-receipts/'.$invoice->id, 'public'),
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'amount' => (int) $item['amount'],
                'payment_date' => $item['payment_date'] ?? null,
                'payment_time' => $item['payment_time'] ?? null,
                'tracking_number' => $item['tracking_number'] ?? null,
                'bank_account_id' => $item['bank_account_id'] ?? $activeAccountId,
                'uploaded_by_customer_id' => $customer->id,
            ]);
        }

        return redirect()
            ->back()
            ->with('message', __('Payment receipt(s) uploaded successfully.'));
    }
}
