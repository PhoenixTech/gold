<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\QuantityPieceStatus;
use App\Events\InvoiceCompleted;
use App\Events\InvoiceFailed;
use App\Events\InvoiceSucceed;
use App\Jobs\SendDeliveryCodeSms;
use App\Models\BankAccount;
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
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceLifecycleTransitionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $courier;

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

        $this->courier = User::factory()->courier()->create(['name' => 'Courier Tester']);
        $this->courier->assignRole('courier');

        $this->customer = Customer::factory()->create([
            'name' => 'Sara Mohammadi',
            'mobile' => '09129998877',
        ]);

        $this->category = Category::factory()->create();

        $this->seedSettings();
    }

    public function test_all_ten_statuses_are_reachable_via_display_status_key_and_state_transitions(): void
    {
        $pending = Invoice::factory()->pending()->create();
        $this->assertSame(Invoice::PENDING, $pending->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $pending->displayStatusKey());

        $awaiting = Invoice::factory()->awaitingPayment()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $awaiting->status);
        $this->assertSame(Invoice::WAITING_RECEIPT, $awaiting->displayStatusKey());

        $waitingReceipt = Invoice::factory()->waitingReceipt()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $waitingReceipt->status);
        $this->assertTrue($waitingReceipt->isOfflineCardPayment());
        $this->assertTrue($waitingReceipt->needsReceiptUpload());
        $this->assertSame(Invoice::WAITING_RECEIPT, $waitingReceipt->displayStatusKey());

        $waitingConfirmation = Invoice::factory()->waitingConfirmation()->create();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $waitingConfirmation->status);
        $this->assertTrue($waitingConfirmation->hasUploadedReceipt());
        $this->assertTrue($waitingConfirmation->isWaitingPaymentConfirmation());
        $this->assertSame(Invoice::WAITING_CONFIRMATION, $waitingConfirmation->displayStatusKey());

        $paid = Invoice::factory()->paid()->create();
        $this->assertSame(Invoice::PAID, $paid->status);
        $this->assertSame(Invoice::PAID, $paid->displayStatusKey());

        $processing = Invoice::factory()->processing()->create();
        $this->assertSame(Invoice::PROCESSING, $processing->status);
        $this->assertSame(Invoice::PROCESSING, $processing->displayStatusKey());

        $outForDelivery = Invoice::factory()->outForDelivery()->create();
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $outForDelivery->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $outForDelivery->displayStatusKey());

        $completed = Invoice::factory()->completed()->create();
        $this->assertSame(Invoice::COMPLETED, $completed->status);
        $this->assertSame(Invoice::COMPLETED, $completed->displayStatusKey());

        $canceled = Invoice::factory()->canceled()->create();
        $this->assertSame(Invoice::CANCELED, $canceled->status);
        $this->assertSame(Invoice::CANCELED, $canceled->displayStatusKey());

        $failed = Invoice::factory()->failed()->create();
        $this->assertSame(Invoice::FAILED, $failed->status);
        $this->assertSame(Invoice::FAILED, $failed->displayStatusKey());

        $invoice = Invoice::factory()->pending()->create();
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());

        $invoice->status = Invoice::AWAITING_PAYMENT;
        $invoice->save();
        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = 500000;
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $invoice->id;
        $receipt->path = 'receipts/test.jpg';
        $receipt->original_name = 'test.jpg';
        $receipt->amount = 500000;
        $receipt->uploaded_by_customer_id = $invoice->customer_id;
        $receipt->save();

        $invoice->refresh();
        $this->assertSame(Invoice::WAITING_CONFIRMATION, $invoice->displayStatusKey());

        $invoice->status = Invoice::PAID;
        $invoice->save();
        $this->assertSame(Invoice::PAID, $invoice->displayStatusKey());

        $invoice->status = Invoice::PROCESSING;
        $invoice->save();
        $this->assertSame(Invoice::PROCESSING, $invoice->displayStatusKey());

        $invoice->status = Invoice::OUT_FOR_DELIVERY;
        $invoice->save();
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->displayStatusKey());

        $invoice->status = Invoice::COMPLETED;
        $invoice->save();
        $this->assertSame(Invoice::COMPLETED, $invoice->displayStatusKey());

        $invoice->status = Invoice::CANCELED;
        $invoice->save();
        $this->assertSame(Invoice::CANCELED, $invoice->displayStatusKey());

        $invoice->status = Invoice::FAILED;
        $invoice->save();
        $this->assertSame(Invoice::FAILED, $invoice->displayStatusKey());
    }

    public function test_valid_online_payment_transition_path_to_paid(): void
    {
        Event::fake([InvoiceSucceed::class]);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 1_500_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_500_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 1_500_000);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'ORD-'.uniqid();
        $payment->type = 'ONLINE';
        $payment->status = Payment::PENDING;
        $payment->amount = 1_500_000;
        $payment->save();

        $this->assertSame('ONLINE', $payment->type);
        $this->assertSame(Payment::PENDING, $payment->status);

        $invoice->storeSuccessPayment($payment->id, 'REF-ONLINE-1001', '6037991234567890', $this->admin);

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->displayStatusKey());
        $this->assertSame(Payment::SUCCESS, $payment->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->fresh()->status);

        Event::assertDispatched(InvoiceSucceed::class);
    }

    public function test_valid_offline_receipt_upload_and_admin_four_point_payment_approval(): void
    {
        Storage::fake('public');
        Event::fake([InvoiceSucceed::class]);

        $bankAccount = BankAccount::create([
            'bank_name' => 'Mellat',
            'account_holder_name' => 'Zhonella Gallery',
            'card_number' => '6104337812345678',
            'account_number' => '123456789',
            'iban' => 'IR123456789012345678901234',
            'is_active' => true,
        ]);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->waitingReceipt()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 2_000_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 2_000_000);

        $this->assertSame(Invoice::WAITING_RECEIPT, $invoice->displayStatusKey());

        $file = UploadedFile::fake()->image('deposit_slip.jpg', 600, 400);

        $response = $this->actingAs($this->customer, 'customer')
            ->post(route('client.invoice.receipts.store', $invoice), [
                'receipts' => [
                    [
                        'amount' => 2_000_000,
                        'payment_date' => '1403/07/01',
                        'payment_time' => '11:30',
                        'tracking_number' => 'TRK-998877',
                        'bank_account_id' => $bankAccount->id,
                        'slip' => $file,
                    ],
                ],
            ]);

        $response->assertRedirect();
        $invoice->refresh();

        $this->assertTrue($invoice->hasUploadedReceipt());
        $this->assertSame(Invoice::WAITING_CONFIRMATION, $invoice->displayStatusKey());
        $this->assertSame(0, $invoice->remainingReceiptBalance());

        $approvalResponse = $this->actingAs($this->admin, 'web')
            ->post(route('admin.invoice.confirm-payment', $invoice), [
                'receipt_info_checked' => '1',
                'account_selected' => '1',
                'bank_verified' => '1',
                'zero_balance' => '1',
                'bank_account_id' => $bankAccount->id,
            ]);

        $approvalResponse->assertRedirect(route('admin.invoice.edit', $invoice));

        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->displayStatusKey());
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->fresh()->status);

        Event::assertDispatched(InvoiceSucceed::class);
    }

    public function test_admin_packaging_courier_assignment_and_courier_pin_verification(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $invoice = Invoice::factory()->paid()->courier()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 3_000_000,
        ]);

        $deliveryService = app(DeliveryService::class);

        $deliveryService->applyAdminStatus($invoice, Invoice::PROCESSING, null);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->displayStatusKey());

        $deliveryService->applyAdminStatus($invoice, Invoice::OUT_FOR_DELIVERY, $this->courier);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->displayStatusKey());

        $delivery = $invoice->fresh()->activeDelivery;
        $this->assertNotNull($delivery);
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);

        $deliveryService->accept($delivery, $this->courier);
        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);

        $code = Cache::get(DeliveryService::codeCacheKey($delivery));
        $this->assertNotEmpty($code);

        $this->actingAs($this->courier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => $code,
            ])
            ->assertRedirect();

        $this->assertSame(DeliveryStatus::Delivered, $delivery->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->displayStatusKey());

        Event::assertDispatched(InvoiceCompleted::class);
    }

    public function test_gallery_pickup_delivery_path_to_completed(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $invoice = Invoice::factory()->pickup()->paid()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 4_000_000,
        ]);

        $this->assertTrue($invoice->isPickup());
        $this->assertNull($invoice->address_id);
        $this->assertFalse($invoice->requiresDeliveryCode());

        $deliveryService = app(DeliveryService::class);

        $deliveryService->applyAdminStatus($invoice, Invoice::PROCESSING, null);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);

        $deliveryService->applyAdminStatus($invoice, Invoice::COMPLETED, null);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->displayStatusKey());

        Event::assertDispatched(InvoiceCompleted::class);
    }

    public function test_admin_payment_decline_path(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 1_800_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->waitingConfirmation()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_800_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 1_800_000);

        $this->assertSame(QuantityPieceStatus::Sold, $quantity->fresh()->status);

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.invoice.decline-payment', $invoice), [
                'reason' => 'Deposit receipt unreadable',
            ]);

        $response->assertRedirect(route('admin.invoice.edit', $invoice));

        $this->assertSame(Invoice::CANCELED, $invoice->fresh()->status);
        $this->assertSame(Invoice::CANCELED, $invoice->fresh()->displayStatusKey());
        $this->assertSame('Deposit receipt unreadable', $invoice->fresh()->declinedReceiptReason());
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_scheduled_offline_expiration_path_releases_stock(): void
    {
        Event::fake([InvoiceFailed::class]);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_200_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->waitingReceipt()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 2_200_000,
            'created_at' => now()->subHours(4),
        ]);
        $this->createOrder($invoice, $product, $quantity, 2_200_000);

        $invoice->expireOfflinePayment();

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(Invoice::FAILED, $invoice->fresh()->displayStatusKey());
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        Event::assertDispatched(InvoiceFailed::class);

        $product2 = $this->createProduct();
        $quantity2 = $this->createQuantity($product2, 1_200_000);
        $quantity2->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product2);

        $invoice2 = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 1_200_000,
        ]);
        $this->createOrder($invoice2, $product2, $quantity2, 1_200_000);

        app(DeliveryService::class)->applyAdminStatus($invoice2, Invoice::CANCELED, null);

        $this->assertSame(Invoice::CANCELED, $invoice2->fresh()->status);
        $this->assertSame(Invoice::CANCELED, $invoice2->fresh()->displayStatusKey());
        $this->assertSame(QuantityPieceStatus::Available, $quantity2->fresh()->status);
        $this->assertSame('IN_STOCK', $product2->fresh()->stock_status);
    }

    public function test_online_payment_failure_path_releases_stock(): void
    {
        Event::fake([InvoiceFailed::class]);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_700_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = Invoice::factory()->awaitingPayment()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 2_700_000,
        ]);
        $this->createOrder($invoice, $product, $quantity, 2_700_000);

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'ORD-'.uniqid();
        $payment->type = 'ONLINE';
        $payment->status = Payment::PENDING;
        $payment->amount = 2_700_000;
        $payment->save();

        $invoice->storeFailPayment($payment->id, 'Gateway bank error');

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(Invoice::FAILED, $invoice->fresh()->displayStatusKey());
        $this->assertSame(Payment::FAIL, $payment->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        Event::assertDispatched(InvoiceFailed::class);

        $product2 = $this->createProduct();
        $quantity2 = $this->createQuantity($product2, 3_100_000);
        $quantity2->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product2);

        $invoice2 = Invoice::factory()->pending()->create([
            'customer_id' => $this->customer->id,
            'total_price' => 3_100_000,
        ]);
        $this->createOrder($invoice2, $product2, $quantity2, 3_100_000);

        $failingGateway = new class implements \App\Contracts\Payment
        {
            public static function registerService() {}

            public static function getName(): string
            {
                return 'zibal';
            }

            public static function getType(): string
            {
                return 'ONLINE';
            }

            public static function isActive(): bool
            {
                return true;
            }

            public static function getLogo()
            {
                return '';
            }

            public function request(int $amount, string $callbackUrl, array $additionalData = []): array
            {
                throw new \RuntimeException('Payment gateway communication error');
            }

            public function goToBank() {}

            public function verify(): array
            {
                return [];
            }
        };
        $this->app->instance(config('xshop.payment.active_gateway').'-gateway', $failingGateway);

        $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $invoice2->hash))
            ->assertRedirect();

        $this->assertSame(Invoice::FAILED, $invoice2->fresh()->status);
        $this->assertSame(Invoice::FAILED, $invoice2->fresh()->displayStatusKey());
        $this->assertSame(QuantityPieceStatus::Available, $quantity2->fresh()->status);
        $this->assertSame('IN_STOCK', $product2->fresh()->stock_status);
    }

    public function test_guardrails_prevent_paying_or_uploading_receipts_for_completed_or_canceled_invoice(): void
    {
        Storage::fake('public');

        $completed = Invoice::factory()->completed()->create([
            'customer_id' => $this->customer->id,
        ]);

        $payResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $completed->hash));
        $payResponse->assertSessionHasErrors();
        $this->assertSame(Invoice::COMPLETED, $completed->fresh()->status);

        $receiptResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('client.invoice.receipts.store', $completed), [
                'receipts' => [UploadedFile::fake()->image('slip.jpg')],
            ]);
        $receiptResponse->assertSessionHasErrors();
        $this->assertSame(0, $completed->paymentReceipts()->count());

        $canceled = Invoice::factory()->canceled()->create([
            'customer_id' => $this->customer->id,
            'created_at' => now()->subHours(2),
        ]);

        $payCanceledResponse = $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $canceled->hash));
        $payCanceledResponse->assertSessionHasErrors();
        $this->assertSame(Invoice::CANCELED, $canceled->fresh()->status);

        $receiptCanceledResponse = $this->actingAs($this->customer, 'customer')
            ->post(route('client.invoice.receipts.store', $canceled), [
                'receipts' => [UploadedFile::fake()->image('slip.jpg')],
            ]);
        $receiptCanceledResponse->assertSessionHasErrors();
        $this->assertSame(0, $canceled->paymentReceipts()->count());
    }

    public function test_guardrail_verifying_courier_delivery_with_incorrect_pin_fails(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        $delivery = Delivery::factory()->accepted()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'code_hash' => Hash::make('4242'),
        ]);

        $response = $this->actingAs($this->courier, 'web')
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => '0000',
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertSame(1, $delivery->fresh()->failed_attempts);
        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_guardrail_admin_cannot_complete_courier_delivery_without_customer_pin(): void
    {
        $invoice = Invoice::factory()->outForDelivery()->courier()->create([
            'customer_id' => $this->customer->id,
        ]);

        Delivery::factory()->accepted()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'code_hash' => Hash::make('4242'),
        ]);

        try {
            app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::COMPLETED, null);
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $this->assertTrue(isset($e->errors()['status']));
        }

        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
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
            'weight' => 2.0,
            'code' => 'Q-'.uniqid(),
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
