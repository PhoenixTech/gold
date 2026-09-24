<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        protected CartQuoteService $quote,
        protected ProductPriceCalculator $calculator,
    ) {}

    public function processCheckout(Customer $customer, array $data): Invoice
    {
        $deliveryType = $data['delivery_type'] ?? 'address';
        $isPickup = $deliveryType === 'pickup';

        if (! $customer->isCheckoutReady($isPickup)) {
            throw ValidationException::withMessages([
                'checkout' => $isPickup
                    ? __('Please complete your name and mobile before checkout.')
                    : __('Please complete your name, mobile and address before checkout.'),
            ]);
        }

        if (! $isPickup) {
            $address = $customer->addresses()->whereKey($data['address_id'])->first();
            if (! $address) {
                throw ValidationException::withMessages([
                    'address_id' => __('Selected address is invalid'),
                ]);
            }

            if (! $address->isTehran()) {
                throw ValidationException::withMessages([
                    'address_id' => __('Direct shipping to non-Tehran provinces is currently unavailable. Please select gallery pickup or a Tehran delivery address.'),
                ]);
            }
        }

        $this->quote->assertValidForCheckout();

        $activeBankAccount = BankAccount::activeAccount();
        if ($activeBankAccount === null) {
            throw ValidationException::withMessages([
                'payment_method' => __('No active bank account is configured. Please contact support.'),
            ]);
        }

        $invoice = DB::transaction(function () use ($customer, $data, $isPickup): Invoice {
            $invoice = new Invoice;
            $invoice->customer_id = $customer->id;
            $invoice->count = array_sum($data['count']);
            $invoice->desc = $data['desc'] ?? null;
            $invoice->status = Invoice::AWAITING_PAYMENT;
            $invoice->delivery_type = $isPickup ? 'pickup' : 'address';

            if ($isPickup) {
                $invoice->address_id = null;
                $invoice->transport_id = null;
                $invoice->transport_price = 0;
                $invoice->is_third_party = false;
                $invoice->recipient_name = null;
                $invoice->recipient_mobile = null;
                $invoice->recipient_national_id = null;
            } else {
                $invoice->address_id = $data['address_id'];
                $transport = Transport::query()->findOrFail($data['transport_id']);
                $invoice->transport_id = $transport->id;
                $invoice->transport_price = $transport->price;

                $isThirdParty = ! empty($data['is_third_party']);
                $invoice->is_third_party = $isThirdParty;
                if ($isThirdParty) {
                    $invoice->recipient_name = $data['recipient_name'] ?? null;
                    $invoice->recipient_mobile = $data['recipient_mobile'] ?? null;
                    $invoice->recipient_national_id = $data['recipient_national_id'] ?? null;
                } else {
                    $invoice->recipient_name = null;
                    $invoice->recipient_mobile = null;
                    $invoice->recipient_national_id = null;
                }
            }

            if (! empty($data['discount_id'])) {
                $invoice->discount_id = $data['discount_id'];
            }

            $invoice->save();

            $productsTotal = 0;
            foreach ($data['product_id'] as $i => $productId) {
                $product = Product::query()->lockForUpdate()->findOrFail($productId);
                if ($product->isBelowBuyPrice()) {
                    throw ValidationException::withMessages([
                        'product_id' => __('This product is not available for purchase'),
                    ]);
                }

                $order = new Order;
                $order->product_id = $product->id;
                $order->invoice_id = $invoice->id;
                $order->count = (int) $data['count'][$i];

                $quantityId = $data['quantity_id'][$i] ?? null;

                if ($product->availableQuantities()->exists()) {
                    if ($quantityId === null || $quantityId === '') {
                        throw ValidationException::withMessages([
                            'quantity_id' => __('You need to select one stock piece'),
                        ]);
                    }

                    $quantity = Quantity::query()
                        ->where('product_id', $product->id)
                        ->whereKey($quantityId)
                        ->lockForUpdate()
                        ->first();

                    if ($quantity === null || $quantity->count <= 0) {
                        throw ValidationException::withMessages([
                            'quantity_id' => __('Selected stock piece is not available'),
                        ]);
                    }

                    if ($order->count > 1) {
                        throw ValidationException::withMessages([
                            'count' => __('Each stock piece can only be purchased once'),
                        ]);
                    }

                    $order->quantity_id = $quantity->id;
                    $order->price_total = $this->quote->unitPrice($product, $quantity) * $order->count;
                    $order->data = $quantity->data;
                    $order->save();

                    $quantity->markSold();
                    $this->calculator->syncProductAggregates($product->fresh());
                } elseif ($quantityId !== null && $quantityId !== '') {
                    $quantity = Quantity::query()->whereKey($quantityId)->lockForUpdate()->firstOrFail();
                    $order->quantity_id = $quantity->id;
                    $order->price_total = $this->quote->unitPrice($product, $quantity) * $order->count;
                    $order->data = $quantity->data;
                    $order->save();

                    $quantity->markSold();
                    $this->calculator->syncProductAggregates($product->fresh());
                } else {
                    $order->price_total = $this->quote->unitPrice($product, null) * $order->count;
                    $order->save();
                }

                $productsTotal += $order->price_total;
            }

            if ($invoice->discount_id) {
                $discount = Discount::query()
                    ->whereKey($invoice->discount_id)
                    ->where(function ($query) {
                        $query->where('expire', '>=', now())
                            ->orWhereNull('expire');
                    })
                    ->first();
                if ($discount) {
                    $productsTotal = $discount->type === 'PERCENT'
                        ? (int) (((100 - $discount->amount) * $productsTotal) / 100)
                        : max(0, $productsTotal - (int) $discount->amount);
                } else {
                    $invoice->discount_id = null;
                }
            }

            $invoice->total_price = $productsTotal + (int) $invoice->transport_price;
            $invoice->save();

            return $invoice;
        });

        $payableAmount = (int) (($invoice->total_price - $invoice->credit_price) * config('app.currency.factor'));

        $invoice->storePaymentRequest(
            'CARD-'.$invoice->hash.'-'.time(),
            $payableAmount,
            null,
            'CARD',
            'card-to-card'
        );

        $payment = $invoice->payments()->latest('id')->first();
        if ($payment) {
            $payment->status = Payment::PENDING;
            $payment->meta = array_merge($payment->meta ?? [], $activeBankAccount->toPaymentMeta());
            $payment->save();
        }

        $this->clearCart($customer);

        return $invoice;
    }

    public function clearCart(?Customer $customer = null): void
    {
        if ($customer) {
            $customer->card = null;
            $customer->save();
        } elseif (auth('customer')->check()) {
            $current = auth('customer')->user();
            $current->card = null;
            $current->save();
        }

        \Cookie::expire('card');
        \Cookie::expire('q');
        $this->quote->forget();
    }
}
