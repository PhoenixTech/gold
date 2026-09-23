<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Enums\QuantityPieceStatus;
use App\Events\InvoiceCompleted;
use App\Events\InvoiceFailed;
use App\Models\Address;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Transport;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceStockRestorationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $courier;

    private Customer $customer;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

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

    public function test_store_fail_payment_releases_reserved_stock_and_updates_product_status(): void
    {
        Event::fake([InvoiceFailed::class]);

        $product = $this->createProduct(['buy_price' => 1_000_000]);
        $quantity = $this->createQuantity($product, 2_500_000);

        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);
        $product->refresh();
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame('OUT_STOCK', $product->stock_status);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 2_500_000);
        $this->createOrder($invoice, $product, $quantity, 2_500_000);

        $payment = $this->createPayment($invoice, Payment::PENDING, 2_500_000);

        $invoice->storeFailPayment($payment->id, 'Gateway declined');

        $invoice->refresh();
        $payment->refresh();
        $quantity->refresh();
        $product->refresh();

        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(Payment::FAIL, $payment->status);
        $this->assertSame('Gateway declined', $payment->comment);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->status);
        $this->assertSame(1, $quantity->count);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
        $this->assertSame(2_500_000, $product->price);

        Event::assertDispatched(InvoiceFailed::class, function ($event) use ($invoice, $payment) {
            return $event->invoice->id === $invoice->id && $event->payment->id === $payment->id;
        });
    }

    public function test_store_fail_payment_is_idempotent_and_preserves_single_stock_count(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 3_000_000);

        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 3_000_000);
        $this->createOrder($invoice, $product, $quantity, 3_000_000);

        $payment = $this->createPayment($invoice, Payment::PENDING, 3_000_000);

        $invoice->storeFailPayment($payment->id, 'Failure 1');
        $this->assertSame(1, $quantity->fresh()->count);
        $this->assertSame(1, $product->fresh()->stock_quantity);

        $invoice->storeFailPayment($payment->id, 'Failure 2');
        $this->assertSame(1, $quantity->fresh()->count);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_store_fail_payment_releases_multiple_pieces_across_multiple_orders(): void
    {
        $productA = $this->createProduct(['buy_price' => 500_000]);
        $quantityA1 = $this->createQuantity($productA, 1_500_000);
        $quantityA2 = $this->createQuantity($productA, 1_800_000);

        $productB = $this->createProduct(['buy_price' => 800_000]);
        $quantityB = $this->createQuantity($productB, 2_200_000);

        $quantityA1->markSold();
        $quantityA2->markSold();
        $quantityB->markSold();

        app(ProductPriceCalculator::class)->syncProductAggregates($productA);
        app(ProductPriceCalculator::class)->syncProductAggregates($productB);

        $this->assertSame('OUT_STOCK', $productA->fresh()->stock_status);
        $this->assertSame('OUT_STOCK', $productB->fresh()->stock_status);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 5_500_000);
        $this->createOrder($invoice, $productA, $quantityA1, 1_500_000);
        $this->createOrder($invoice, $productA, $quantityA2, 1_800_000);
        $this->createOrder($invoice, $productB, $quantityB, 2_200_000);

        $payment = $this->createPayment($invoice, Payment::PENDING, 5_500_000);

        $invoice->storeFailPayment($payment->id, 'Transaction canceled');

        $this->assertSame(QuantityPieceStatus::Available, $quantityA1->fresh()->status);
        $this->assertSame(1, $quantityA1->fresh()->count);
        $this->assertSame(QuantityPieceStatus::Available, $quantityA2->fresh()->status);
        $this->assertSame(1, $quantityA2->fresh()->count);
        $this->assertSame(QuantityPieceStatus::Available, $quantityB->fresh()->status);
        $this->assertSame(1, $quantityB->fresh()->count);

        $this->assertSame(2, $productA->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $productA->fresh()->stock_status);
        $this->assertSame(1, $productB->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $productB->fresh()->stock_status);
    }

    public function test_store_fail_payment_does_not_release_stock_if_payment_already_succeeded(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::PAID, 2_000_000);
        $this->createOrder($invoice, $product, $quantity, 2_000_000);

        $payment = $this->createPayment($invoice, Payment::SUCCESS, 2_000_000);

        $resultPayment = $invoice->storeFailPayment($payment->id, 'Late failure message');

        $this->assertSame(Payment::SUCCESS, $resultPayment->status);
        $this->assertSame(Invoice::PAID, $invoice->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->fresh()->status);
        $this->assertSame(0, $quantity->fresh()->count);
        $this->assertSame('OUT_STOCK', $product->fresh()->stock_status);
    }

    public function test_store_fail_payment_with_unknown_payment_id_still_fails_and_releases_stock(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 1_200_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 1_200_000);
        $this->createOrder($invoice, $product, $quantity, 1_200_000);

        $invoice->storeFailPayment(999999, 'Phantom payment ID');

        $this->assertSame(Invoice::FAILED, $invoice->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $quantity->fresh()->count);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_apply_admin_status_failed_releases_reserved_stock(): void
    {
        $product = $this->createProduct(['buy_price' => 1_000_000]);
        $quantity = $this->createQuantity($product, 4_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 4_000_000);
        $this->createOrder($invoice, $product, $quantity, 4_000_000);

        app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::FAILED, null);

        $invoice->refresh();
        $quantity->refresh();
        $product->refresh();

        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->status);
        $this->assertSame(1, $quantity->count);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
    }

    public function test_apply_admin_status_failed_cancels_out_for_delivery_and_releases_stock(): void
    {
        $transport = $this->createTransport('Courier', true);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 3_500_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::OUT_FOR_DELIVERY, 3_500_000, $transport);
        $this->createOrder($invoice, $product, $quantity, 3_500_000);

        $delivery = Delivery::create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'status' => DeliveryStatus::Pending,
            'code_hash' => Hash::make('1234'),
        ]);

        app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::FAILED, null);

        $invoice->refresh();
        $delivery->refresh();
        $quantity->refresh();
        $product->refresh();

        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(DeliveryStatus::Rejected, $delivery->status);
        $this->assertFalse($delivery->isOpen());
        $this->assertSame(QuantityPieceStatus::Available, $quantity->status);
        $this->assertSame(1, $quantity->count);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
    }

    public function test_round_trip_admin_transitions_toggle_stock_between_failed_and_paid(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 2_000_000);
        $this->createOrder($invoice, $product, $quantity, 2_000_000);

        $deliveryService = app(DeliveryService::class);

        $deliveryService->applyAdminStatus($invoice, Invoice::FAILED, null);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);

        $deliveryService->applyAdminStatus($invoice, Invoice::PAID, null);
        $this->assertSame(QuantityPieceStatus::Sold, $quantity->fresh()->status);
        $this->assertSame(0, $quantity->fresh()->count);

        $deliveryService->applyAdminStatus($invoice, Invoice::FAILED, null);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->fresh()->status);
        $this->assertSame(1, $quantity->fresh()->count);
        $this->assertSame(1, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
    }

    public function test_sync_product_aggregates_sets_in_stock_when_pieces_are_restored(): void
    {
        $calculator = app(ProductPriceCalculator::class);
        $product = $this->createProduct(['buy_price' => 1_000_000]);

        $quantity1 = $this->createQuantity($product, 2_000_000);
        $quantity2 = $this->createQuantity($product, 2_500_000);

        $quantity1->markSold();
        $quantity2->markSold();
        $calculator->syncProductAggregates($product);

        $product->refresh();
        $this->assertSame(0, $product->stock_quantity);
        $this->assertSame('OUT_STOCK', $product->stock_status);

        $quantity1->markAvailable();
        $calculator->syncProductAggregates($product);

        $product->refresh();
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame(2_000_000, $product->price);
        $this->assertSame('IN_STOCK', $product->stock_status);

        $quantity2->markAvailable();
        $calculator->syncProductAggregates($product);

        $product->refresh();
        $this->assertSame(2, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
    }

    public function test_sync_product_aggregates_stays_out_stock_when_restored_piece_is_unpriced_or_below_buy_price(): void
    {
        $calculator = app(ProductPriceCalculator::class);

        $productA = $this->createProduct(['buy_price' => 0]);
        $quantityA = $this->createQuantity($productA, 0);
        $quantityA->markAvailable();
        $calculator->syncProductAggregates($productA);
        $this->assertSame(1, $productA->fresh()->stock_quantity);
        $this->assertSame('OUT_STOCK', $productA->fresh()->stock_status);

        $productB = $this->createProduct(['buy_price' => 5_000_000]);
        $quantityB = $this->createQuantity($productB, 3_000_000);
        $quantityB->markAvailable();
        $calculator->syncProductAggregates($productB);
        $this->assertSame(1, $productB->fresh()->stock_quantity);
        $this->assertSame('OUT_STOCK', $productB->fresh()->stock_status);

        $quantityB->price = 6_000_000;
        $quantityB->save();
        $calculator->syncProductAggregates($productB);
        $this->assertSame(1, $productB->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $productB->fresh()->stock_status);
    }

    public function test_invoice_completed_dispatched_when_admin_completes_non_courier_invoice(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $transport = $this->createTransport('Gallery Pickup', false);
        $invoice = $this->createInvoice(Invoice::PAID, 1_000_000, $transport);

        app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::COMPLETED, null);

        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);

        Event::assertDispatched(InvoiceCompleted::class, function ($event) use ($invoice) {
            return $event->invoice->id === $invoice->id;
        });
    }

    public function test_invoice_completed_dispatched_when_courier_confirms_delivery(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $transport = $this->createTransport('Courier Service', true);
        $invoice = $this->createInvoice(Invoice::OUT_FOR_DELIVERY, 2_000_000, $transport);

        $delivery = Delivery::create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'status' => DeliveryStatus::Accepted,
            'code_hash' => Hash::make('4321'),
        ]);

        app(DeliveryService::class)->confirm($delivery, $this->courier, '4321');

        $this->assertSame(DeliveryStatus::Delivered, $delivery->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);

        Event::assertDispatched(InvoiceCompleted::class, function ($event) use ($invoice) {
            return $event->invoice->id === $invoice->id;
        });
    }

    public function test_confirm_delivery_alias_dispatches_invoice_completed(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $transport = $this->createTransport('Courier Express', true);
        $invoice = $this->createInvoice(Invoice::OUT_FOR_DELIVERY, 2_000_000, $transport);

        $delivery = Delivery::create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'status' => DeliveryStatus::Accepted,
            'code_hash' => Hash::make('7890'),
        ]);

        app(DeliveryService::class)->confirmDelivery($delivery, $this->courier, '7890');

        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);

        Event::assertDispatched(InvoiceCompleted::class, function ($event) use ($invoice) {
            return $event->invoice->id === $invoice->id;
        });
    }

    public function test_invoice_completed_is_not_redispatched_if_already_completed(): void
    {
        $transport = $this->createTransport('In-Store', false);
        $invoice = $this->createInvoice(Invoice::COMPLETED, 1_000_000, $transport);

        Event::fake([InvoiceCompleted::class]);

        app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::COMPLETED, null);

        Event::assertNotDispatched(InvoiceCompleted::class);
    }

    public function test_admin_cannot_complete_courier_invoice_directly_without_courier_code(): void
    {
        Event::fake([InvoiceCompleted::class]);

        $transport = $this->createTransport('Courier Guarded', true);
        $invoice = $this->createInvoice(Invoice::OUT_FOR_DELIVERY, 1_000_000, $transport);

        Delivery::create([
            'invoice_id' => $invoice->id,
            'courier_id' => $this->courier->id,
            'status' => DeliveryStatus::Pending,
            'code_hash' => Hash::make('9999'),
        ]);

        $this->expectException(ValidationException::class);

        try {
            app(DeliveryService::class)->applyAdminStatus($invoice, Invoice::COMPLETED, null);
        } finally {
            $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
            Event::assertNotDispatched(InvoiceCompleted::class);
        }
    }

    public function test_client_controller_pay_exception_releases_reserved_stock(): void
    {
        $mockGateway = new class implements \App\Contracts\Payment
        {
            public static function registerService() {}

            public static function getName(): string
            {
                return 'failing';
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
                throw new \RuntimeException('Gateway connection error');
            }

            public function goToBank() {}

            public function verify(): array
            {
                return [];
            }
        };

        $this->app->instance('failing-gateway', $mockGateway);
        config(['xshop.payment.active_gateway' => 'failing']);

        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 2_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::PENDING, 2_000_000);
        $this->createOrder($invoice, $product, $quantity, 2_000_000);

        $this->actingAs($this->customer, 'customer')
            ->get(route('client.pay', $invoice->hash));

        $invoice->refresh();
        $quantity->refresh();
        $product->refresh();

        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->status);
        $this->assertSame(1, $quantity->count);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
    }

    public function test_offline_expire_command_releases_reserved_stock(): void
    {
        $product = $this->createProduct();
        $quantity = $this->createQuantity($product, 3_000_000);
        $quantity->markSold();
        app(ProductPriceCalculator::class)->syncProductAggregates($product);

        $invoice = $this->createInvoice(Invoice::AWAITING_PAYMENT, 3_000_000);
        $invoice->created_at = now()->subHours(4);
        $invoice->save();

        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'CARD-'.uniqid();
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = 3_000_000;
        $payment->save();

        $this->createOrder($invoice, $product, $quantity, 3_000_000);

        $this->artisan('offline:expire')->assertSuccessful();

        $invoice->refresh();
        $quantity->refresh();
        $product->refresh();

        $this->assertSame(Invoice::FAILED, $invoice->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity->status);
        $this->assertSame(1, $quantity->count);
        $this->assertSame(1, $product->stock_quantity);
        $this->assertSame('IN_STOCK', $product->stock_status);
    }

    public function test_multi_invoice_inventory_isolation_and_piece_restoration(): void
    {
        $calculator = app(ProductPriceCalculator::class);
        $product = $this->createProduct();

        $quantity1 = $this->createQuantity($product, 1_000_000);
        $quantity2 = $this->createQuantity($product, 1_200_000);
        $quantity3 = $this->createQuantity($product, 1_500_000);

        $quantity1->markSold();
        $quantity2->markSold();
        $calculator->syncProductAggregates($product);

        $this->assertSame(1, $product->fresh()->stock_quantity);

        $invoice1 = $this->createInvoice(Invoice::AWAITING_PAYMENT, 1_000_000);
        $this->createOrder($invoice1, $product, $quantity1, 1_000_000);

        $invoice2 = $this->createInvoice(Invoice::AWAITING_PAYMENT, 1_200_000);
        $this->createOrder($invoice2, $product, $quantity2, 1_200_000);

        $payment1 = $this->createPayment($invoice1, Payment::PENDING, 1_000_000);
        $invoice1->storeFailPayment($payment1->id, 'Declined');

        $this->assertSame(QuantityPieceStatus::Available, $quantity1->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Sold, $quantity2->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity3->fresh()->status);
        $this->assertSame(2, $product->fresh()->stock_quantity);

        app(DeliveryService::class)->applyAdminStatus($invoice2, Invoice::FAILED, null);

        $this->assertSame(QuantityPieceStatus::Available, $quantity1->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity2->fresh()->status);
        $this->assertSame(QuantityPieceStatus::Available, $quantity3->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertSame('IN_STOCK', $product->fresh()->stock_status);
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
            'code' => 'P-'.uniqid(),
        ]);
    }

    private function createTransport(string $title, bool $requiresDeliveryCode): Transport
    {
        $transport = new Transport;
        $transport->title = $title;
        $transport->price = 0;
        $transport->requires_delivery_code = $requiresDeliveryCode;
        $transport->save();

        return $transport;
    }

    private function createPayment(Invoice $invoice, string $status, int $amount): Payment
    {
        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->order_id = 'ORD-'.uniqid();
        $payment->status = $status;
        $payment->amount = $amount;
        $payment->save();

        return $payment;
    }

    private function createInvoice(string $status, int $total, ?Transport $transport = null): Invoice
    {
        $address = new Address;
        $address->customer_id = $this->customer->id;
        $address->address = 'Tehran St 10';
        $address->zip = '1234567890';
        $address->save();

        if ($transport === null) {
            $transport = $this->createTransport('Standard Post', false);
        }

        $invoice = new Invoice;
        $invoice->customer_id = $this->customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->status = $status;
        $invoice->total_price = $total;
        $invoice->count = 1;
        $invoice->save();

        return $invoice;
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
