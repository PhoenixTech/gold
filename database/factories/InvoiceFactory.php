<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Transport;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'customer_id' => fn () => Customer::query()->inRandomOrder()->first()?->id ?? Customer::factory()->create()->id,
            'address_id' => function (array $attributes) {
                if (array_key_exists('delivery_type', $attributes) && in_array($attributes['delivery_type'], ['pickup', 'gallery_pickup'], true)) {
                    return null;
                }

                $customerId = $attributes['customer_id'] ?? null;
                if ($customerId instanceof \Closure) {
                    $customerId = $customerId();
                }
                if ($customerId instanceof Model) {
                    $customerId = $customerId->id;
                }
                if (! $customerId) {
                    $customer = Customer::query()->inRandomOrder()->first() ?? Customer::factory()->create();
                    $customerId = $customer->id;
                }

                $address = Address::query()->where('customer_id', $customerId)->first();
                if (! $address) {
                    $address = new Address;
                    $address->customer_id = $customerId;
                    $address->address = 'Tehran, Valiasr St';
                    $address->save();
                }

                return $address->id;
            },
            'status' => Invoice::PENDING,
            'delivery_type' => 'address',
            'desc' => $this->faker->sentence(),
            'transport_id' => null,
            'transport_price' => 0,
            'total_price' => 100000,
            'count' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function awaitingPayment(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::AWAITING_PAYMENT,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function waitingReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::AWAITING_PAYMENT,
            'created_at' => now(),
            'updated_at' => now(),
        ])->afterCreating(function (Invoice $invoice) {
            if (! $invoice->isOfflineCardPayment()) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'CARD';
                $payment->status = Payment::PENDING;
                $payment->amount = $invoice->total_price ?: 100000;
                $payment->save();
            }
        });
    }

    public function waitingConfirmation(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::AWAITING_PAYMENT,
            'created_at' => now(),
            'updated_at' => now(),
        ])->afterCreating(function (Invoice $invoice) {
            $payment = $invoice->cardPayment();
            if (! $payment) {
                $payment = new Payment;
                $payment->invoice_id = $invoice->id;
                $payment->order_id = $invoice->id;
                $payment->type = 'CARD';
                $payment->status = Payment::PENDING;
                $payment->amount = $invoice->total_price ?: 100000;
                $payment->save();
            }

            if (! $invoice->hasUploadedReceipt()) {
                $receipt = new PaymentReceipt;
                $receipt->payment_id = $payment->id;
                $receipt->invoice_id = $invoice->id;
                $receipt->path = 'payment-receipts/'.$invoice->id.'/slip.jpg';
                $receipt->original_name = 'slip.jpg';
                $receipt->mime = 'image/jpeg';
                $receipt->size = 1024;
                $receipt->amount = $invoice->total_price ?: 100000;
                $receipt->uploaded_by_customer_id = $invoice->customer_id;
                $receipt->save();
            }
        });
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::PAID,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::PROCESSING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function outForDelivery(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::OUT_FOR_DELIVERY,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::COMPLETED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::CANCELED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Invoice::FAILED,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function pickup(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => 'pickup',
            'address_id' => null,
            'transport_id' => null,
            'transport_price' => 0,
        ]);
    }

    public function courier(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_type' => 'address',
        ])->afterCreating(function (Invoice $invoice) {
            if (! $invoice->transport_id) {
                $transport = Transport::query()->where('requires_delivery_code', true)->first();
                if (! $transport) {
                    $transport = new Transport;
                    $transport->title = 'Courier Delivery';
                    $transport->price = 50000;
                    $transport->requires_delivery_code = true;
                    $transport->save();
                }
                $invoice->transport_id = $transport->id;
                $invoice->transport_price = $transport->price;
                $invoice->save();
            }
        });
    }
}
