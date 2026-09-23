<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\QuantityPieceStatus;
use App\Events\InvoiceCompleted;
use App\Events\InvoiceFailed;
use App\Events\InvoiceSucceed;
use App\Jobs\SendDeliveryCodeSms;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Transport;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceLifecycleAdversarialChallengerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $courier;

    private User $otherCourier;

    private Customer $customer;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake([SendDeliveryCodeSms::class]);

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('courier', 'web');

        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->admin->assignRole('admin');

        $this->courier = User::factory()->courier()->create(['name' => 'Assigned Courier']);
        $this->courier->assignRole('courier');

        $this->otherCourier = User::factory()->courier()->create(['name' => 'Other Courier']);
        $this->otherCourier->assignRole('courier');

        $this->customer = Customer::factory()->create([
            'name' => 'Reza Alavi',
            'mobile' => '09121112233',
        ]);

        $this->category = Category::factory()->create();

        $this->seedSettings();
    }

    public function test_adversarial_courier_code_inputs_fail_validation(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        $delivery = Delivery::factory()->accepted()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'code_hash' => Hash::make('4242'),
        ]);

        $invalidCodes = [
            '0000',
            '9999',
            '123',
            '12345',
            'abcd',
            '42a2',
            '',
            ' ',
            '   ',
            "' OR '1'='1",
            '<script>4242</script>',
            '۴۲۴۳',
        ];

        foreach ($invalidCodes as $badCode) {
            $response = $this->actingAs($this->courier, 'web')
                ->post(route('admin.delivery.confirm', $delivery), [
                    'code' => $badCode,
                ]);

            $response->assertSessionHasErrors('code');
            $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);
            $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
        }
    }

    public function test_adversarial_courier_five_failed_attempts_locks_job_and_rejects_valid_pin(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        $delivery = Delivery::factory()->accepted()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'code_hash' => Hash::make('7890'),
            'failed_attempts' => 0,
        ]);

        for ($i = 1; $i <= 4; $i++) {
            $response = $this->actingAs($this->courier, 'web')
                ->post(route('admin.delivery.confirm', $delivery), [
                    'code' => '1111',
                ]);

            $response->assertSessionHasErrors('code');
            $this->assertSame($i, $delivery->fresh()->failed_attempts);
            $this->assertFalse($delivery->fresh()->isLocked());
            $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);
        }

        $fifthResponse = $this->actingAs($this->courier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => '1111',
            ]);

        $fifthResponse->assertSessionHasErrors('code');
        $this->assertSame(5, $delivery->fresh()->failed_attempts);
        $this->assertTrue($delivery->fresh()->isLocked());

        $lockedAttemptWithCorrectCode = $this->actingAs($this->courier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => '7890',
            ]);

        $lockedAttemptWithCorrectCode->assertSessionHasErrors('code');
        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_adversarial_courier_unaccepted_delivery_cannot_be_confirmed(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        $delivery = Delivery::factory()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'status' => DeliveryStatus::Pending,
            'code_hash' => Hash::make('5555'),
        ]);

        $response = $this->actingAs($this->courier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => '5555',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(DeliveryStatus::Pending, $delivery->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_adversarial_other_courier_forbidden_from_confirming_delivery(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        $delivery = Delivery::factory()->accepted()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'code_hash' => Hash::make('6666'),
        ]);

        $response = $this->actingAs($this->otherCourier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => '6666',
            ]);

        $response->assertForbidden();
        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_stock_release_on_admin_payment_decline_restores_all_pieces_and_products(): void
    {
        $productA = $this->createProduct(['buy_price' => 1_000_000]);
        $quantityA = $this->createQuantity($productA, 2_000_000);
        $quantityA->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($productA);

        $productB = $this->createProduct(['buy_price' => 1_500_000]);
        $quantityB = $this->createQuantity($productB, 3_000_000);
        $quantityB->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($productB);

        $this->assertSame('OUT_STOCK', $productA->fresh()->stock_status);
        $this->assertSame('OUT_STOCK', $productB->fresh()->stock_status);

        $invoice = Invoice::factory()->waitingConfirmation()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 5_000_000,
        ]);
        $this->createOrder($invoice, $productA, $quantityA, 2_000_000);
        $this->createOrder($invoice, $productB, $quantityB, 3_000_000);

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.invoice.decline-payment', $invoice), [
                'reason' => 'Invalid bank transfer slip',
            ]);

        $response->assertRedirect(route('admin.invoice.edit', $invoice));

        $this->assertSame(Invoice::CANCELED, $invoice->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantityA->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantityB->fresh()->status);
        $this->assertSame('IN_STOCK', $productA->fresh()->stock_status);
        $this->assertSame('IN_STOCK', $productB->fresh()->stock_status);
        $this->assertSame(1, $productA->fresh()->stock_quantity);
        $this->assertSame(1, $productB->fresh()->stock_quantity);
    }

    public function test_stock_release_on_offline_expiration_restores_stock(): void
    {
        Event::fake([InvoiceFailed::class]);

        $product = $this->createProduct(['buy_price' => 500_000]);
        $quantity = $this->createQuantity($product, 1_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->waitingReceipt()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_000_000,
            'created_at' => now()->subHours(5),
        ]);
        $this->createOrder($invoice, $product, $quantity, 1_000_000);

        $this->assertSame(0, $product->fresh()->stock_quantity);
        $this->assertSame('OUT_STOCK', $product->fresh()->stock_status);

        $invoice->expireOfflinePayment();

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        Event::assertDispatched(InvoiceFailed::class);
    }

    public function test_stock_release_on_online_payment_failure_restores_stock(): void
    {
        Event::fake([InvoiceFailed::class]);

        $product = $this->createProduct(['buy_price' => 700_000]);
        $quantity = $this->createQuantity($product, 1_400_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_400_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 1_400_000);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'ORD-FAIL-1';
        $payment->type = 'ONLINE';
        $payment->status = Payment::PENDING;
        $payment->amount = 1_400_000;
        $payment->save();

        $invoice->storeFailPayment($payment->id, 'Declined by bank terminal');

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(Payment::FAIL, $payment->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        Event::assertDispatched(InvoiceFailed::class);
    }

    public function test_stock_release_on_delivery_service_apply_admin_status_canceled_and_failed(): void
    {
        $product1 = $this->createProduct();
        $quantity1 = $this->createQuantity($product1, 1_000_000);
        $quantity1->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product1);

        $invoice1 = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_000_000,
        ]);
        $this->createOrder($invoice1, $product1, $quantity1, 1_000_000);

        app(DeliveryService::class)->applyAdminStatus($invoice1, Invoice::CANCELED, null);

        $this->assertSame(Invoice::CANCELED, $invoice1->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity1->fresh()->status);
        $this->assertSame('IN_STOCK', $product1->fresh()->stock_status);

        $product2 = $this->createProduct();
        $quantity2 = $this->createQuantity($product2, 2_000_000);
        $quantity2->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product2);

        $invoice2 = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 2_000_000,
        ]);
        $this->createOrder($invoice2, $product2, $quantity2, 2_000_000);

        app(DeliveryService::class)->applyAdminStatus($invoice2, Invoice::FAILED, null);

        $this->assertSame(Invoice::FAILED, $invoice2->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity2->fresh()->status);
        $this->assertSame('IN_STOCK', $product2->fresh()->stock_status);
    }

    public function test_stock_release_is_idempotent_and_safe_with_null_quantities(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 1_100_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_100_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 1_100_000);

        $orderWithoutQuantity = new Order;
        $orderWithoutQuantity->invoice_id = $invoice->id;
        $orderWithoutQuantity->product_id = $product->id;
        $orderWithoutQuantity->quantity_id = null;
        $orderWithoutQuantity->count = 1;
        $orderWithoutQuantity->price_total = 0;
        $orderWithoutQuantity->save();

        $invoice->releaseReservedStock();
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        $invoice->releaseReservedStock();
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_all_ten_statuses_reachability_via_workflow_transitions(): void
    {
        Event::fake([InvoiceCompleted::class, InvoiceFailed::class, InvoiceSucceed::class]);

        $invoice = Invoice::factory()->pending()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 5_000_000,
        ]);
        $this->assertSame(Invoice::PENDING, $invoice->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());

        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->save();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->fresh()->displayStatusKey());

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = 5_000_000;
        $payment->save();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->fresh()->displayStatusKey());

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $invoice->id;
        $receipt->path = 'receipts/test_receipt.jpg';
        $receipt->original_name = 'test_receipt.jpg';
        $receipt->amount = 5_000_000;
        $receipt->uploaded_by_customer_id = $this->customer->id;
        $receipt->save();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->fresh()->status);
        $this->assertSame(Invoice::WAITING_CONFIRMATION, $invoice->fresh()->displayStatusKey());

        $invoice->storeSuccessPayment($payment->id, 'REF-9988', '6037991234567890', $this->admin);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->displayStatusKey());

        $deliveryService = app(DeliveryService::class);
        $deliveryService->applyAdminStatus($invoice, Invoice::PROCESSING, null);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->displayStatusKey());

        $transport = new Transport;
        $transport->title = 'Courier Delivery';
        $transport->price = 50000;
        $transport->requires_delivery_code = true;
        $transport->save();
        $invoice->transport_id = $transport->id;
        $invoice->delivery_type = 'address';
        $invoice->save();
        $invoice->refresh();

        $deliveryService->applyAdminStatus($invoice, Invoice::OUT_FOR_DELIVERY, $this->courier);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->displayStatusKey());

        $delivery = $invoice->fresh()->activeDelivery;
        $this->assertNotNull($delivery);
        $deliveryService->accept($delivery, $this->courier);

        $code = Cache::get(DeliveryService::codeCacheKey($delivery));
        $deliveryService->confirm($delivery, $this->courier, $code);

        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->displayStatusKey());

        $invoiceCancel = Invoice::factory()->waitingConfirmation()->create();
        $deliveryService->applyAdminStatus($invoiceCancel, Invoice::CANCELED, null);
        $this->assertSame(Invoice::CANCELED, $invoiceCancel->fresh()->status);
        $this->assertSame(Invoice::CANCELED, $invoiceCancel->fresh()->displayStatusKey());

        $invoiceFail = Invoice::factory()->awaitingPayment()->create();
        $deliveryService->applyAdminStatus($invoiceFail, Invoice::FAILED, null);
        $this->assertSame(Invoice::FAILED, $invoiceFail->fresh()->status);
        $this->assertSame(Invoice::FAILED, $invoiceFail->fresh()->displayStatusKey());
    }

    private function createProduct(array $overrides = []): Product
    {
        return Product::factory()->create(array_merge([
            'user_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => 1,
            'stock_quantity' => 0,
            'stock_status' => 'OUT_STOCK',
            'buy_price' => 0,
            'price' => 0,
        ], $overrides));
    }

    private function createQuantity(Product $product, int $price): Quantity
    {
        return Quantity::factory()->create([
            'product_id' => $product->id,
            'count' => 1,
            'price' => $price,
            'status' => QuantityPieceStatus::Available,
            'weight' => 1.5,
            'code' => 'Q-CHALLENGE-'.uniqid(),
        ]);
    }

    private function createOrder(Invoice $invoice, Product $product, Quantity $quantity, int $price): Order
    {
        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = $price;
        $order->save();

        return $order;
    }

    private function seedSettings(): void
    {
        foreach ([
            'gold' => '2000000',
            'silver' => '80000',
            'min' => '105',
        ] as $key => $value) {
            $setting = Setting::query()->firstOrNew(['key' => $key]);
            $setting->section = 'General';
            $setting->type = 'TEXT';
            $setting->title = $key;
            $setting->ltr = true;
            $setting->size = 12;
            $setting->value = $value;
            $setting->raw = $value;
            $setting->save();
        }
    }
}
