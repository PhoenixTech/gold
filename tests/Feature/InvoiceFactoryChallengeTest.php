<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Transport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceFactoryChallengeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->pending()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::PENDING, $invoice->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());
        $this->assertNotNull($invoice->customer_id);
        $this->assertNotNull($invoice->address_id);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::PENDING]);
        $this->assertDatabaseHas('customers', ['id' => $invoice->customer_id]);
        $this->assertDatabaseHas('addresses', ['id' => $invoice->address_id, 'customer_id' => $invoice->customer_id]);
    }

    public function test_awaiting_payment_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->awaitingPayment()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());
        $this->assertNotNull($invoice->customer_id);
        $this->assertNotNull($invoice->address_id);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::AWAITING_PAYMENT]);
    }

    public function test_waiting_receipt_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->waitingReceipt()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());
        $this->assertTrue($invoice->isOfflineCardPayment());
        $this->assertTrue($invoice->needsReceiptUpload());
        $this->assertFalse($invoice->hasUploadedReceipt());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::AWAITING_PAYMENT]);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'type' => 'CARD',
            'status' => Payment::PENDING,
        ]);
    }

    public function test_waiting_confirmation_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->waitingConfirmation()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame(Invoice::WAITING_CONFIRMATION, $invoice->displayStatusKey());
        $this->assertTrue($invoice->isOfflineCardPayment());
        $this->assertTrue($invoice->hasUploadedReceipt());
        $this->assertTrue($invoice->isWaitingPaymentConfirmation());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::AWAITING_PAYMENT]);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'type' => 'CARD',
            'status' => Payment::PENDING,
        ]);
        $this->assertDatabaseHas('payment_receipts', [
            'invoice_id' => $invoice->id,
            'uploaded_by_customer_id' => $invoice->customer_id,
        ]);
    }

    public function test_paid_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->paid()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::PAID, $invoice->status);
        $this->assertSame(Invoice::PAID, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::PAID]);
    }

    public function test_processing_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->processing()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::PROCESSING, $invoice->status);
        $this->assertSame(Invoice::PROCESSING, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::PROCESSING]);
    }

    public function test_out_for_delivery_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->outForDelivery()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::OUT_FOR_DELIVERY]);
    }

    public function test_completed_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->completed()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::COMPLETED, $invoice->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::COMPLETED]);
    }

    public function test_canceled_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->canceled()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::CANCELED, $invoice->status);
        $this->assertSame(Invoice::CANCELED, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::CANCELED]);
    }

    public function test_failed_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->failed()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(Invoice::FAILED, $invoice->displayStatusKey());
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => Invoice::FAILED]);
    }

    public function test_pickup_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoice = Invoice::factory()->pickup()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame('pickup', $invoice->delivery_type);
        $this->assertNull($invoice->address_id);
        $this->assertNull($invoice->transport_id);
        $this->assertSame(0, (int) $invoice->transport_price);
        $this->assertTrue($invoice->isPickup());
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'delivery_type' => 'pickup',
            'address_id' => null,
        ]);
    }

    public function test_courier_state_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, Transport::count());

        $invoice = Invoice::factory()->courier()->create();

        $this->assertTrue($invoice->exists);
        $this->assertSame('address', $invoice->delivery_type);
        $this->assertNotNull($invoice->transport_id);
        $this->assertSame(50000, (int) $invoice->transport_price);
        $this->assertDatabaseHas('transports', [
            'id' => $invoice->transport_id,
            'requires_delivery_code' => true,
        ]);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'delivery_type' => 'address',
            'transport_id' => $invoice->transport_id,
        ]);
    }

    public function test_all_twelve_states_created_sequentially_in_single_database_session(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $pending = Invoice::factory()->pending()->create();
        $this->assertSame(Invoice::PENDING, $pending->status);

        $awaiting = Invoice::factory()->awaitingPayment()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $awaiting->status);

        $waitingReceipt = Invoice::factory()->waitingReceipt()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $waitingReceipt->status);
        $this->assertTrue($waitingReceipt->isOfflineCardPayment());

        $waitingConfirmation = Invoice::factory()->waitingConfirmation()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $waitingConfirmation->status);
        $this->assertTrue($waitingConfirmation->hasUploadedReceipt());

        $paid = Invoice::factory()->paid()->create();
        $this->assertSame(Invoice::PAID, $paid->status);

        $processing = Invoice::factory()->processing()->create();
        $this->assertSame(Invoice::PROCESSING, $processing->status);

        $outForDelivery = Invoice::factory()->outForDelivery()->create();
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $outForDelivery->status);

        $completed = Invoice::factory()->completed()->create();
        $this->assertSame(Invoice::COMPLETED, $completed->status);

        $canceled = Invoice::factory()->canceled()->create();
        $this->assertSame(Invoice::CANCELED, $canceled->status);

        $failed = Invoice::factory()->failed()->create();
        $this->assertSame(Invoice::FAILED, $failed->status);

        $pickup = Invoice::factory()->pickup()->create();
        $this->assertSame('pickup', $pickup->delivery_type);
        $this->assertNull($pickup->address_id);

        $courier = Invoice::factory()->courier()->create();
        $this->assertSame('address', $courier->delivery_type);
        $this->assertNotNull($courier->transport_id);

        $this->assertSame(12, Invoice::count());
    }

    public function test_batch_count_creation_on_empty_database(): void
    {
        $this->assertSame(0, Customer::count());
        $this->assertSame(0, Invoice::count());

        $invoices = Invoice::factory()->count(10)->create();

        $this->assertCount(10, $invoices);
        $this->assertSame(10, Invoice::count());
        foreach ($invoices as $inv) {
            $this->assertNotNull($inv->customer_id);
            $this->assertNotNull($inv->address_id);
            $this->assertSame(Invoice::PENDING, $inv->status);
        }
    }

    public function test_courier_state_reuses_existing_transport(): void
    {
        $this->assertSame(0, Transport::count());

        $first = Invoice::factory()->courier()->create();
        $second = Invoice::factory()->courier()->create();

        $this->assertSame(1, Transport::count());
        $this->assertSame($first->transport_id, $second->transport_id);
    }

    public function test_explicit_customer_override_creates_address_for_that_customer(): void
    {
        $customer = Customer::factory()->create();
        $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertDatabaseHas('addresses', [
            'id' => $invoice->address_id,
            'customer_id' => $customer->id,
        ]);
    }
}
