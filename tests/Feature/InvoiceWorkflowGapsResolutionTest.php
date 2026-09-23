<?php

namespace Tests\Feature;

use App\Contracts\Payment as PaymentGatewayContract;
use App\Enums\QuantityPieceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceWorkflowGapsResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->admin->assignRole('admin');
        $this->customer = Customer::factory()->create();
    }

    public function test_gap1_order_board_distinguishes_gallery_pickup_from_courier(): void
    {
        Invoice::factory()->pickup()->create([
            'status' => Invoice::PROCESSING,
        ]);

        Invoice::factory()->courier()->create([
            'status' => Invoice::PROCESSING,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.order-board.index'));
        $response->assertOk();

        $response->assertSee(__('In-person gallery pickup'));
        $response->assertSee(__('Awaiting customer visit to gallery'));
    }

    public function test_gap2_customer_invoice_shows_processing_and_completed_banners(): void
    {
        $processingInvoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::PROCESSING,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $processingInvoice->hash));

        $response->assertOk();
        $response->assertSee(__('Order is being prepared'));
        $response->assertSee(__('Your order is confirmed and being prepared in the warehouse.'));

        $completedInvoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::COMPLETED,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $completedInvoice->hash));

        $response->assertOk();
        $response->assertSee(__('Order delivered'));
        $response->assertSee(__('Your order has been delivered successfully. Thank you for your purchase.'));
    }

    public function test_gap3_admin_can_request_receipt_reupload_extending_deadline_and_preserving_stock(): void
    {
        Storage::fake('public');

        $product = Product::factory()->create();
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'status' => QuantityPieceStatus::Sold,
            'count' => 0,
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::AWAITING_PAYMENT,
            'created_at' => now()->subHour(),
        ]);

        Order::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity_id' => $quantity->id,
            'count' => 1,
            'price_total' => 1000000,
        ]);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'type' => 'CARD',
            'status' => Payment::PENDING,
            'order_id' => 'CARD-1234',
        ]);

        $file = UploadedFile::fake()->image('receipt.jpg');
        $path = $file->store('receipts', 'public');

        PaymentReceipt::create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'path' => $path,
            'original_name' => 'receipt.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
            'amount' => $invoice->total_price,
            'uploaded_by_customer_id' => $this->customer->id,
        ]);

        $this->assertSame(Invoice::WAITING_CONFIRMATION, $invoice->fresh()->displayStatusKey());

        $initialDeadline = $invoice->offlinePaymentDeadline();

        $response = $this->actingAs($this->admin)->post(
            route('admin.invoice.request-receipt-reupload', $invoice),
            ['reason' => 'Image too blurry to read transaction reference']
        );

        $response->assertRedirect(route('admin.invoice.edit', $invoice));

        $invoice->refresh();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());
        $this->assertSame('Image too blurry to read transaction reference', $invoice->declinedReceiptReason());
        $this->assertCount(0, $invoice->paymentReceipts);
        $this->assertTrue($invoice->offlinePaymentDeadline()->gt($initialDeadline));

        $quantity->refresh();
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->status);

        $customerResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $invoice->hash));

        $customerResponse->assertOk();
        $customerResponse->assertSee(__('Previous receipt was declined'));
        $customerResponse->assertSee('Image too blurry to read transaction reference');
        $customerResponse->assertSee(__('Register Payment Receipt'));
    }

    public function test_gap4_customer_invoice_delivery_banners_for_pickup_courier_and_post(): void
    {
        $pickupInvoice = Invoice::factory()->pickup()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::OUT_FOR_DELIVERY,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $pickupInvoice->hash));

        $response->assertOk();
        $response->assertSee(__('Your order is ready for pickup at the gallery.'));
        $response->assertDontSee(__('A 4-digit code was sent to your mobile. Give it only to the courier.'));

        $courierTransport = Transport::factory()->create([
            'title' => 'Courier',
            'requires_delivery_code' => true,
        ]);
        $courierInvoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $courierTransport->id,
            'delivery_type' => 'courier',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $courierInvoice->hash));

        $response->assertOk();
        $response->assertSee(__('A 4-digit code was sent to your mobile. Give it only to the courier.'));

        $postalTransport = Transport::factory()->create([
            'title' => 'Post',
            'requires_delivery_code' => false,
        ]);
        $postalInvoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $postalTransport->id,
            'delivery_type' => 'post',
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $postalInvoice->hash));

        $response->assertOk();
        $response->assertSee(__('Your order has been dispatched and is on its way to you.'));
        $response->assertDontSee(__('A 4-digit code was sent to your mobile. Give it only to the courier.'));
    }

    public function test_gap5_online_payment_retry_reserves_stock_and_blocks_unavailable(): void
    {
        $product = Product::factory()->create();
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'status' => QuantityPieceStatus::Available,
            'count' => 1,
        ]);

        $invoice = Invoice::factory()->create([
            'customer_id' => $this->customer->id,
            'status' => Invoice::FAILED,
            'total_price' => 5000000,
            'credit_price' => 0,
        ]);

        Order::factory()->create([
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'quantity_id' => $quantity->id,
            'count' => 1,
            'price_total' => 5000000,
        ]);

        $response = $this->actingAs($this->customer, 'customer')
            ->get(route('client.invoice', $invoice->hash));

        $response->assertOk();
        $response->assertSee(__('Retry payment'));

        $gatewayMock = $this->mock(PaymentGatewayContract::class);
        $gatewayMock->shouldReceive('getName')->andReturn('mock');
        $gatewayMock->shouldReceive('request')->once()->andReturn([
            'order_id' => 'ORD-1234',
            'token' => 'TOK-5678',
        ]);
        $gatewayMock->shouldReceive('goToBank')->once()->andReturn(redirect('https://bank.example.com'));

        app()->instance('mock-gateway', $gatewayMock);
        config(['xshop.payment.active_gateway' => 'mock']);

        $payResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $invoice->hash));

        $payResponse->assertRedirect('https://bank.example.com');

        $quantity->refresh();
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->status);
        $this->assertSame(0, (int) $quantity->count);

        $invoice->refresh();
        $this->assertSame(Invoice::PENDING, $invoice->status);

        $quantity->markSold();
        $invoice->status = Invoice::FAILED;
        $invoice->save();

        $failPayResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $invoice->hash));

        $failPayResponse->assertSessionHasErrors();
    }
}
