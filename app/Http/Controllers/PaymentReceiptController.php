<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;

class PaymentReceiptController extends Controller
{
    public function store(Request $request, Invoice $invoice)
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

        $payment = $invoice->payments()
            ->where('type', 'CARD')
            ->latest('id')
            ->first();

        if ($payment === null || $payment->status !== Payment::PENDING) {
            return redirect()
                ->back()
                ->withErrors(__('No pending card payment found for this invoice.'));
        }

        $request->validate([
            'receipts' => ['required', 'array', 'min:1', 'max:10'],
            'receipts.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ], [
            'receipts.required' => __('Please select at least one receipt file.'),
            'receipts.*.mimes' => __('Receipts must be images or PDF files.'),
            'receipts.*.max' => __('Each receipt may not be larger than 5MB.'),
        ]);

        foreach ($request->file('receipts') as $file) {
            $path = $file->store('payment-receipts/'.$invoice->id, 'public');

            PaymentReceipt::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploaded_by_customer_id' => $customer->id,
            ]);
        }

        return redirect()
            ->back()
            ->with('message', __('Payment receipt(s) uploaded successfully.'));
    }
}
