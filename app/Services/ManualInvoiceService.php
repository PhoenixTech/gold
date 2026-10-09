<?php

namespace App\Services;

use App\Enums\InvoiceSource;
use App\Enums\ShopPaymentMethod;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Quantity;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualInvoiceService
{
    public function __construct(
        protected ProductPriceCalculator $calculator,
        protected DeliveryService $deliveries,
    ) {}

    public function customerByMobile(string $mobile): ?Customer
    {
        return Customer::query()->where('mobile', $mobile)->first();
    }

    public function mobileBelongsToRemovedCustomer(string $mobile): bool
    {
        return Customer::onlyTrashed()->where('mobile', $mobile)->exists();
    }

    public function sellablePieces(string $search, array $excludeIds, int $perPage): LengthAwarePaginator
    {
        return Quantity::query()
            ->available()
            ->whereNotIn('id', $excludeIds)
            ->whereHas('product')
            ->whereDoesntHave('product', fn (Builder $product) => $product->belowBuyPrice())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $match) use ($search): void {
                    $match->where('code', 'like', '%'.$search.'%')
                        ->orWhereHas('product', fn (Builder $product) => $product
                            ->where('name', 'like', '%'.$search.'%')
                            ->orWhere('sku', 'like', '%'.$search.'%'));
                });
            })
            ->with('product')
            ->orderBy('product_id')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Quantity $piece) => [
                'piece' => $piece,
                'price' => $this->calculator->priceForQuantity($piece->product, $piece),
            ]);
    }

    public function lines(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        $pieces = Quantity::query()->with('product')->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(function (int $id) use ($pieces): array {
            $piece = $pieces->get($id);
            $product = $piece?->product;

            return [
                'id' => $id,
                'piece' => $piece,
                'price' => $product !== null ? $this->calculator->priceForQuantity($product, $piece) : 0,
                'available' => $piece !== null && $product !== null && $piece->isAvailable(),
            ];
        });
    }

    public function total(Collection $lines): int
    {
        return (int) $lines->sum('price');
    }

    public function withPieces(array $current, array $ids): array
    {
        $fresh = array_values(array_diff(array_values(array_unique(array_map('intval', $ids))), $current));

        if ($fresh === []) {
            return $current;
        }

        $pieces = Quantity::query()->with('product')->whereIn('id', $fresh)->get()->keyBy('id');
        $this->validatePieceAvailability($pieces, $fresh);

        return array_values(array_merge($current, $fresh));
    }

    public function create(array $draft, User $admin): Invoice
    {
        return DB::transaction(function () use ($draft, $admin): Invoice {
            $customer = $this->resolveCustomer($draft['customer'] ?? []);
            $pieces = $this->lockSellablePieces($draft['quantity_ids']);

            $prices = [];
            foreach ($pieces as $piece) {
                $prices[$piece->id] = $this->calculator->priceForQuantity($piece->product, $piece);
            }
            $orderTotal = array_sum($prices);

            $rawPayments = (array) ($draft['payments'] ?? []);
            $validPayments = array_values(array_filter($rawPayments, fn ($p) => is_array($p) && (int) ($p['amount'] ?? 0) > 0));
            $paidTotal = array_sum(array_map(fn ($p) => (int) $p['amount'], $validPayments));

            if ($paidTotal > $orderTotal && $orderTotal > 0) {
                throw ValidationException::withMessages([
                    'payments' => __('Total payments (:paid Toman) cannot exceed the invoice total (:total Toman).', [
                        'paid' => number_format($paidTotal),
                        'total' => number_format($orderTotal),
                    ]),
                ]);
            }

            $isFullyPaid = $paidTotal >= $orderTotal && $orderTotal > 0;
            $willHandover = $isFullyPaid && ($draft['handover'] ?? false);

            $invoice = new Invoice;
            $invoice->customer_id = $customer->id;
            $invoice->status = $isFullyPaid ? Invoice::PAID : Invoice::AWAITING_PAYMENT;
            $invoice->delivery_type = 'pickup';
            $invoice->is_third_party = false;
            $invoice->transport_price = 0;
            $invoice->count = $pieces->count();
            $invoice->total_price = $orderTotal;
            $invoice->desc = $draft['note'] ?: null;
            $invoice->source = InvoiceSource::Manual;
            $invoice->created_by = $admin->id;
            $invoice->save();

            foreach ($pieces as $piece) {
                $order = new Order;
                $order->product_id = $piece->product_id;
                $order->invoice_id = $invoice->id;
                $order->quantity_id = $piece->id;
                $order->count = 1;
                $order->price_total = $prices[$piece->id];
                $order->data = $piece->data;
                $order->save();

                $piece->markSold();
            }

            foreach ($pieces->pluck('product')->unique('id') as $product) {
                $this->calculator->syncProductAggregates($product);
            }

            if ($validPayments !== []) {
                foreach ($validPayments as $index => $paymentData) {
                    $this->recordSinglePayment($invoice, $paymentData, $admin, $index);
                }
            } else {
                $bankAccount = BankAccount::activeAccount();
                if ($bankAccount !== null) {
                    $payment = new Payment;
                    $payment->type = 'CARD';
                    $payment->status = Payment::PENDING;
                    $payment->amount = $invoice->total_price;
                    $payment->order_id = 'SHOP-'.$invoice->hash.'-'.time();
                    $payment->meta = array_merge($bankAccount->toPaymentMeta(), [
                        'method' => ShopPaymentMethod::CardToCard->value,
                        'recorded_by' => $admin->id,
                        'recorded_at' => now()->toDateTimeString(),
                    ]);
                    $invoice->payments()->save($payment);
                }
            }

            if ($willHandover) {
                $this->deliveries->applyAdminStatus($invoice, Invoice::READY_FOR_PICKUP, null);
                $this->deliveries->applyAdminStatus($invoice, Invoice::COMPLETED, null);
            }

            return $invoice;
        });
    }

    public function addPaymentToInvoice(Invoice $invoice, array $data, User $admin, ?UploadedFile $slip = null): Payment
    {
        $amount = (int) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => __('Enter the payment amount.')]);
        }

        $remaining = $invoice->remainingReceiptBalance();
        if ($amount > $remaining && $remaining > 0) {
            throw ValidationException::withMessages([
                'amount' => __('Payment amount cannot exceed the remaining balance (:remaining Toman).', [
                    'remaining' => number_format($remaining),
                ]),
            ]);
        }

        return DB::transaction(function () use ($invoice, $data, $admin, $slip): Payment {
            $payment = $this->recordSinglePayment($invoice, $data, $admin, time(), $slip);

            $invoice->refresh();
            if ($invoice->remainingReceiptBalance() === 0 && $invoice->status === Invoice::AWAITING_PAYMENT) {
                $invoice->status = Invoice::PAID;
                $invoice->save();
            }

            logAdmin(__METHOD__, Invoice::class, $invoice->id);

            return $payment;
        });
    }

    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['id'])) {
            $customer = Customer::query()->find($data['id']);

            if ($customer !== null) {
                return $customer;
            }
        }

        $mobile = (string) ($data['mobile'] ?? '');

        if ($mobile === '') {
            throw ValidationException::withMessages(['mobile' => __('Enter the customer mobile number.')]);
        }

        $existing = $this->customerByMobile($mobile);

        if ($existing !== null) {
            return $existing;
        }

        if ($this->mobileBelongsToRemovedCustomer($mobile)) {
            throw ValidationException::withMessages([
                'mobile' => __('This mobile belongs to a removed customer. Restore it from the customers list first.'),
            ]);
        }

        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => __('Enter the customer name for a new customer.')]);
        }

        $customer = new Customer;
        $customer->name = $name;
        $customer->mobile = $mobile;
        $customer->save();

        return $customer;
    }

    private function lockSellablePieces(array $ids): Collection
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));

        if ($ids === []) {
            throw ValidationException::withMessages([
                'quantity_ids' => __('Add at least one stock piece before continuing.'),
            ]);
        }

        $pieces = Quantity::query()->with('product')->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');
        $this->validatePieceAvailability($pieces, $ids);

        return collect($ids)->map(fn (int $id) => $pieces->get($id))->values();
    }

    private function validatePieceAvailability(Collection $pieces, array $ids): void
    {
        $errors = [];

        foreach ($ids as $id) {
            $piece = $pieces->get($id);

            if ($piece === null || ! $piece->isAvailable() || $piece->product === null) {
                $errors[] = __('Stock piece :code is no longer available.', ['code' => $piece?->code ?: '#'.$id]);

                continue;
            }

            if ($piece->product->isBelowBuyPrice()) {
                $errors[] = __('This product is not available for purchase');
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages(['quantity_ids' => $errors]);
        }
    }

    private function recordSinglePayment(Invoice $invoice, array $data, User $admin, int|string $index, ?UploadedFile $slip = null): Payment
    {
        $now = now()->toDateTimeString();
        $method = ShopPaymentMethod::tryFrom((string) ($data['method'] ?? '')) ?? ShopPaymentMethod::Pos;
        $amount = (int) ($data['amount'] ?? 0);
        $supplierId = ! empty($data['supplier_id']) ? (int) $data['supplier_id'] : null;
        $bankAccountId = ! empty($data['bank_account_id']) ? (int) $data['bank_account_id'] : null;
        $trackingNumber = ! empty($data['tracking_number']) ? trim((string) $data['tracking_number']) : null;
        $paymentDate = ! empty($data['payment_date']) ? trim((string) $data['payment_date']) : null;
        $paymentTime = ! empty($data['payment_time']) ? trim((string) $data['payment_time']) : null;
        $slipFile = $slip ?? ($data['slip'] ?? null);

        $bankAccount = $bankAccountId ? BankAccount::query()->find($bankAccountId) : BankAccount::activeAccount();
        $supplier = $supplierId ? Supplier::query()->find($supplierId) : null;

        $meta = [
            'method' => $method->value,
            'channel' => Payment::CHANNEL_IN_STORE,
            'recorded_by' => $admin->id,
            'recorded_at' => $now,
            'confirmed_by' => $admin->id,
            'confirmed_by_name' => $admin->name ?? $admin->email,
            'confirmed_at' => $now,
            'payment_date' => $paymentDate,
            'payment_time' => $paymentTime,
        ];

        if ($bankAccount !== null) {
            $meta['bank_account_id'] = $bankAccount->id;
            $meta['bank_account_name'] = $bankAccount->bank_name;
            $meta['card_number'] = $bankAccount->card_number;
        }

        if ($supplier !== null) {
            $meta['supplier_id'] = $supplier->id;
            $meta['supplier_name'] = $supplier->name;
        }

        $payment = new Payment;
        $payment->type = 'CARD';
        $payment->status = Payment::SUCCESS;
        $payment->amount = $amount;
        $payment->supplier_id = $supplierId;
        $payment->order_id = 'SHOP-'.$invoice->hash.'-'.$index.'-'.uniqid();
        $payment->reference_id = $trackingNumber;
        $payment->comment = ! empty($data['note']) ? trim((string) $data['note']) : null;
        $payment->meta = $meta;
        $invoice->payments()->save($payment);

        if ($slipFile instanceof UploadedFile) {
            $path = $slipFile->store('receipts', 'public');
            PaymentReceipt::query()->create([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'path' => $path,
                'original_name' => $slipFile->getClientOriginalName(),
                'mime' => $slipFile->getMimeType(),
                'size' => $slipFile->getSize(),
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_time' => $paymentTime,
                'tracking_number' => $trackingNumber,
                'bank_account_id' => $bankAccount?->id,
            ]);
        }

        return $payment;
    }
}
