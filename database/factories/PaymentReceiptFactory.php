<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentReceipt>
 */
class PaymentReceiptFactory extends Factory
{
    protected $model = PaymentReceipt::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'invoice_id' => Invoice::factory(),
            'path' => 'payment-receipts/example.jpg',
            'original_name' => 'receipt.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
            'uploaded_by_customer_id' => Customer::factory(),
        ];
    }
}
