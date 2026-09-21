<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

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
        $bankAccount = BankAccount::activeAccount();

        $invoice->loadMissing([
            'customer',
            'payments',
            'paymentReceipts',
        ]);

        return view('client.customer.receipt', compact('title', 'subtitle', 'invoice', 'bankAccount'));
    }

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

        if ($invoice->isOfflinePaymentExpired()) {
            return redirect()
                ->back()
                ->withErrors(__('The offline payment deadline for this invoice has passed. The invoice was failed, please contact support.'));
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

        $rawReceipts = $request->input('receipts');
        if ($rawReceipts === null) {
            $rawReceipts = $request->file('receipts') ?? ($request->all()['receipts'] ?? []);
        }

        $isStructured = is_array($rawReceipts) && isset($rawReceipts[0]) && is_array($rawReceipts[0]);

        if ($isStructured) {
            $request->validate([
                'receipts' => ['required', 'array', 'min:1', 'max:10'],
                'receipts.*.amount' => ['nullable', 'numeric'],
                'receipts.*.payment_date' => ['nullable', 'string', 'max:255'],
                'receipts.*.payment_time' => ['nullable', 'string', 'max:255'],
                'receipts.*.tracking_number' => ['nullable', 'string', 'max:255'],
                'receipts.*.bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
                'receipts.*.slip' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            ], [
                'receipts.required' => __('Please enter at least one payment receipt.'),
                'receipts.*.slip.mimes' => __('Receipts must be images or PDF files.'),
                'receipts.*.slip.max' => __('Each receipt may not be larger than 5MB.'),
            ]);

            $activeAccountId = BankAccount::activeAccount()?->id;
            $receiptsData = $request->input('receipts', []);
            if (empty($receiptsData) && isset($request->all()['receipts'])) {
                $receiptsData = $request->all()['receipts'];
            }

            foreach ($receiptsData as $index => $item) {
                $file = $request->file("receipts.{$index}.slip");
                if (! $file && isset($item['slip']) && $item['slip'] instanceof UploadedFile) {
                    $file = $item['slip'];
                }

                $path = '';
                $originalName = '';
                $mime = null;
                $size = null;

                if ($file) {
                    $path = $file->store('payment-receipts/'.$invoice->id, 'public');
                    $originalName = $file->getClientOriginalName();
                    $mime = $file->getClientMimeType();
                    $size = $file->getSize();
                }

                $bankAccountId = ! empty($item['bank_account_id'])
                    ? (int) $item['bank_account_id']
                    : $activeAccountId;

                PaymentReceipt::query()->create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'path' => $path,
                    'original_name' => $originalName,
                    'mime' => $mime,
                    'size' => $size,
                    'amount' => isset($item['amount']) && $item['amount'] !== '' ? (int) $item['amount'] : null,
                    'payment_date' => $item['payment_date'] ?? null,
                    'payment_time' => $item['payment_time'] ?? null,
                    'tracking_number' => $item['tracking_number'] ?? null,
                    'bank_account_id' => $bankAccountId,
                    'uploaded_by_customer_id' => $customer->id,
                ]);
            }
        } else {
            $request->validate([
                'receipts' => ['required', 'array', 'min:1', 'max:10'],
                'receipts.*' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
            ], [
                'receipts.required' => __('Please select at least one receipt file.'),
                'receipts.*.mimes' => __('Receipts must be images or PDF files.'),
                'receipts.*.max' => __('Each receipt may not be larger than 5MB.'),
            ]);

            $activeAccountId = BankAccount::activeAccount()?->id;

            foreach ($request->file('receipts') as $file) {
                $path = $file->store('payment-receipts/'.$invoice->id, 'public');

                PaymentReceipt::query()->create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                    'bank_account_id' => $activeAccountId,
                    'uploaded_by_customer_id' => $customer->id,
                ]);
            }
        }

        return redirect()
            ->back()
            ->with('message', __('Payment receipt(s) uploaded successfully.'));
    }
}
