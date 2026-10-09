<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ShopPaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\ManualInvoiceCustomerRequest;
use App\Http\Requests\ManualInvoiceItemsRequest;
use App\Http\Requests\ManualInvoicePaymentRequest;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Services\ManualInvoiceDraft;
use App\Services\ManualInvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class ManualInvoiceController extends Controller
{
    private const STEPS = ['customer', 'items', 'payment', 'review', 'complete'];

    public function __construct(
        private ManualInvoiceDraft $draft,
        private ManualInvoiceService $sales,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyAccess('invoice'), 403);

        $step = (string) $request->query('step', 'customer');
        abort_unless(in_array($step, self::STEPS, true), 404);

        if ($step === 'complete') {
            $invoiceHash = (string) $request->query('invoice', '');
            $invoice = Invoice::query()
                ->with(['customer', 'orders.product', 'orders.quantity', 'payments.supplier', 'createdBy'])
                ->where('hash', $invoiceHash)
                ->first();
            abort_unless($invoice, 404);

            return view('admin.invoices.manual.complete', [
                'step' => 'complete',
                'invoice' => $invoice,
                'draft' => $this->draft->get(),
                'lines' => collect(),
                'total' => (int) $invoice->total_price,
                'paidTotal' => $invoice->receivedAmount(),
                'remaining' => $invoice->remainingReceiptBalance(),
            ]);
        }

        $draft = $this->draft->get();

        if ($redirect = $this->missingStep($step, $draft)) {
            return $redirect;
        }

        $lines = $this->sales->lines($draft['quantity_ids']);
        $total = $this->sales->total($lines);
        $paidTotal = array_sum(array_map(fn ($p) => (int) ($p['amount'] ?? 0), $draft['payments'] ?? []));
        $remaining = max(0, $total - $paidTotal);

        $data = [
            'step' => $step,
            'draft' => $draft,
            'lines' => $lines,
            'total' => $total,
            'paidTotal' => $paidTotal,
            'remaining' => $remaining,
        ];

        return match ($step) {
            'customer' => view('admin.invoices.manual.customer', $data),
            'items' => view('admin.invoices.manual.items', $data + [
                'pieces' => $this->sales->sellablePieces(
                    trim((string) $request->query('q', '')),
                    $draft['quantity_ids'],
                    24,
                ),
                'search' => trim((string) $request->query('q', '')),
            ]),
            'payment' => view('admin.invoices.manual.payment', $data + [
                'methods' => ShopPaymentMethod::cases(),
                'bankAccounts' => BankAccount::query()->where('is_active', true)->get(),
                'suppliers' => Supplier::query()->orderBy('last_name')->orderBy('first_name')->get(),
            ]),
            'review' => view('admin.invoices.manual.review', $data + [
                'methods' => ShopPaymentMethod::cases(),
                'bankAccounts' => BankAccount::query()->get()->keyBy('id'),
                'suppliers' => Supplier::query()->get()->keyBy('id'),
            ]),
        };
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasAnyAccess('invoice'), 403);

        $step = (string) $request->input('step');

        if ($step === 'cancel') {
            $this->draft->forget();

            return redirect()
                ->route('admin.invoice.create')
                ->with(['message' => __('The shop sale was discarded.')]);
        }

        abort_unless(in_array($step, self::STEPS, true), 404);

        $draft = $this->draft->get();

        if ($redirect = $this->missingStep($step, $draft)) {
            return $redirect;
        }

        return match ($step) {
            'customer' => $this->saveCustomer(),
            'items' => $this->changePieces($request, $draft),
            'payment' => $this->savePayment($request, $draft),
            'review' => $this->createInvoice($request, $draft),
        };
    }

    private function saveCustomer(): RedirectResponse
    {
        $data = app(ManualInvoiceCustomerRequest::class)->validated();
        $customer = $this->sales->customerByMobile($data['mobile']);
        $draft = $this->draft->get();

        $draft['customer'] = [
            'id' => $customer?->id,
            'mobile' => $data['mobile'],
            'name' => $customer?->name ?: (string) ($data['name'] ?? ''),
        ];
        $this->draft->put($draft);

        return redirect()->route('admin.invoice.create', ['step' => 'items']);
    }

    private function changePieces(Request $request, array $draft): RedirectResponse
    {
        $data = app(ManualInvoiceItemsRequest::class)->validated();

        if ($data['action'] === 'remove') {
            $removed = (int) $data['quantity_id'];
            $draft['quantity_ids'] = array_values(array_filter(
                $draft['quantity_ids'],
                fn (int $id) => $id !== $removed,
            ));
        } else {
            $draft['quantity_ids'] = $this->sales->withPieces(
                $draft['quantity_ids'],
                array_map('intval', $data['quantity_ids']),
            );
        }

        $this->draft->put($draft);

        return redirect()->route('admin.invoice.create', array_filter([
            'step' => 'items',
            'q' => trim((string) $request->input('q', '')),
        ]));
    }

    private function savePayment(Request $request, array $draft): RedirectResponse
    {
        $data = app(ManualInvoicePaymentRequest::class)->validated();

        $processedPayments = [];
        $rawPayments = (array) ($data['payments'] ?? []);

        foreach ($rawPayments as $index => $item) {
            if (! is_array($item) || empty($item['amount'])) {
                continue;
            }

            $entry = [
                'method' => $item['method'] ?? ShopPaymentMethod::Pos->value,
                'amount' => (int) $item['amount'],
                'supplier_id' => ! empty($item['supplier_id']) ? (int) $item['supplier_id'] : null,
                'bank_account_id' => ! empty($item['bank_account_id']) ? (int) $item['bank_account_id'] : null,
                'tracking_number' => ! empty($item['tracking_number']) ? trim((string) $item['tracking_number']) : null,
                'payment_date' => ! empty($item['payment_date']) ? trim((string) $item['payment_date']) : null,
                'payment_time' => ! empty($item['payment_time']) ? trim((string) $item['payment_time']) : null,
                'note' => ! empty($item['note']) ? trim((string) $item['note']) : null,
            ];

            $file = $request->file("payments.{$index}.slip");
            if ($file instanceof UploadedFile) {
                $entry['slip_path'] = $file->store('receipts', 'public');
                $entry['slip_name'] = $file->getClientOriginalName();
                $entry['slip_mime'] = $file->getMimeType();
                $entry['slip_size'] = $file->getSize();
            }

            $processedPayments[] = $entry;
        }

        $draft['payments'] = $processedPayments;
        $draft['payment_step_completed'] = true;
        $draft['handover'] = $request->boolean('handover');
        $draft['note'] = trim((string) ($data['note'] ?? '')) ?: null;
        $this->draft->put($draft);

        return redirect()->route('admin.invoice.create', ['step' => 'review']);
    }

    private function createInvoice(Request $request, array $draft): RedirectResponse
    {
        $invoice = $this->sales->create($draft, $request->user());

        $this->draft->forget();
        logAdmin(__METHOD__, Invoice::class, $invoice->id);

        return redirect()
            ->route('admin.invoice.create', ['step' => 'complete', 'invoice' => $invoice->hash])
            ->with(['message' => __('Shop invoice :hash was created.', ['hash' => $invoice->hash])]);
    }

    private function missingStep(string $step, array $draft): ?RedirectResponse
    {
        if ($step === 'complete') {
            return null;
        }

        if ($step === 'customer') {
            return null;
        }

        if ($draft['customer'] === null) {
            return redirect()
                ->route('admin.invoice.create')
                ->withErrors(__('Enter the customer details first.'));
        }

        if ($step === 'items') {
            return null;
        }

        if ($draft['quantity_ids'] === []) {
            return redirect()
                ->route('admin.invoice.create', ['step' => 'items'])
                ->withErrors(__('Add at least one stock piece before continuing.'));
        }

        if ($step === 'payment') {
            return null;
        }

        if (! ($draft['payment_step_completed'] ?? false)) {
            return redirect()
                ->route('admin.invoice.create', ['step' => 'payment'])
                ->withErrors(__('Complete the payment step first.'));
        }

        return null;
    }
}
