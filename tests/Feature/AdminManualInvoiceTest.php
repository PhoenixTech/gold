<?php

namespace Tests\Feature;

use App\Enums\InvoiceSource;
use App\Enums\QuantityPieceStatus;
use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ManualInvoiceDraft;
use App\Services\ManualInvoiceService;
use App\Services\ProductPriceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminManualInvoiceTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    private function seedMetalSettings(): void
    {
        $setting = Setting::query()->firstOrNew(['key' => 'gold']);
        $setting->section = 'General';
        $setting->type = 'TEXT';
        $setting->title = 'gold';
        $setting->ltr = true;
        $setting->size = 12;
        $setting->value = '2000000';
        $setting->raw = '2000000';
        $setting->save();
    }

    private function makePiece(int $price = 0, int $buyPrice = 0): Quantity
    {
        $product = Product::factory()->create([
            'name' => 'انگشتر '.uniqid(),
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => $price,
            'buy_price' => $buyPrice,
            'stock_quantity' => 1,
            'slug' => 'p-'.uniqid(),
        ]);

        return Quantity::factory()->create([
            'product_id' => $product->id,
            'count' => 1,
            'weight' => 3.5,
            'code' => 'RG-'.uniqid(),
            'data' => json_encode(['weight' => 3.5]),
        ]);
    }

    private function priceOf(Quantity $piece): int
    {
        $piece->loadMissing('product');

        return app(ProductPriceCalculator::class)->priceForQuantity($piece->product, $piece);
    }

    private function step(array $data): TestResponse
    {
        return $this->post(route('admin.invoice.store'), $data);
    }

    private function startWithCustomer(string $mobile = '09121230001', string $name = 'مشتری حضوری'): void
    {
        $this->step(['step' => 'customer', 'mobile' => $mobile, 'name' => $name])
            ->assertRedirect(route('admin.invoice.create', ['step' => 'items']));
    }

    private function addPiece(Quantity $piece): void
    {
        $this->step(['step' => 'items', 'action' => 'add', 'quantity_ids' => [$piece->id]])
            ->assertRedirect(route('admin.invoice.create', ['step' => 'items']));
    }

    public function test_invoice_list_offers_create_button_and_source_filter(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $response = $this->get(route('admin.invoice.index'));

        $response->assertOk();
        $response->assertSee(route('admin.invoice.create'), false);
        $response->assertSee('name="filter[source]"', false);
    }

    public function test_create_page_starts_at_the_customer_step(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $response = $this->get(route('admin.invoice.create'));

        $response->assertOk();
        $response->assertSee('name="mobile"', false);
        $response->assertSee('value="customer"', false);
    }

    public function test_later_steps_redirect_back_when_the_sale_is_incomplete(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $this->get(route('admin.invoice.create', ['step' => 'items']))
            ->assertRedirect(route('admin.invoice.create'));
        $this->get(route('admin.invoice.create', ['step' => 'review']))
            ->assertRedirect(route('admin.invoice.create'));

        $this->startWithCustomer();

        $this->get(route('admin.invoice.create', ['step' => 'payment']))
            ->assertRedirect(route('admin.invoice.create', ['step' => 'items']));

        $this->step(['step' => 'review'])
            ->assertRedirect(route('admin.invoice.create', ['step' => 'items']));

        $this->assertSame(0, Invoice::query()->count());
    }

    public function test_new_customer_needs_a_name(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $this->step(['step' => 'customer', 'mobile' => '09121230002'])
            ->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('customers', ['mobile' => '09121230002']);
    }

    public function test_mobile_of_a_removed_customer_is_rejected(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $removed = Customer::factory()->create(['mobile' => '09121230003']);
        $removed->delete();

        $this->step(['step' => 'customer', 'mobile' => '09121230003', 'name' => 'تکراری'])
            ->assertSessionHasErrors('mobile');
    }

    public function test_full_pos_sale_with_handover_creates_a_completed_shop_invoice(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $admin = $this->actingAsAdmin();
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);

        $this->startWithCustomer('09121230001', 'مشتری حضوری');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [
                [
                    'method' => 'pos',
                    'amount' => $price,
                ],
            ],
            'handover' => '1',
            'note' => 'تحویل همان روز',
        ])->assertRedirect(route('admin.invoice.create', ['step' => 'review']));

        $response = $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();
        $response->assertRedirect(route('admin.invoice.create', ['step' => 'complete', 'invoice' => $invoice->hash]));
        $this->get(route('admin.invoice.create', ['step' => 'complete', 'invoice' => $invoice->hash]))
            ->assertOk()
            ->assertSee(__('Shop invoice created successfully'))
            ->assertSee(__('Print invoice'));

        $this->assertSame(Invoice::COMPLETED, $invoice->status);
        $this->assertSame('pickup', $invoice->delivery_type);
        $this->assertSame($admin->id, $invoice->created_by);
        $this->assertSame('تحویل همان روز', $invoice->desc);
        $this->assertSame($price, (int) $invoice->total_price);
        $this->assertSame(1, $invoice->orders()->count());
        $this->assertSame(QuantityPieceStatus::Sold, $piece->fresh()->status);

        $customer = Customer::query()->where('mobile', '09121230001')->firstOrFail();
        $this->assertSame($customer->id, $invoice->customer_id);
        $this->assertSame('مشتری حضوری', $customer->name);

        $payment = $invoice->payments()->firstOrFail();
        $this->assertSame('CARD', $payment->type);
        $this->assertSame('pos', $payment->meta['method']);
        $this->assertSame(Payment::SUCCESS, $payment->status);
        $this->assertSame($price, (int) $payment->amount);
        $this->assertSame(Payment::CHANNEL_IN_STORE, $payment->meta['channel']);

        $this->assertSame($price, $invoice->receivedAmount());
        $this->assertSame(0, $invoice->remainingReceiptBalance());

        $this->get(route('admin.invoice.create', ['step' => 'items']))
            ->assertRedirect(route('admin.invoice.create'));
    }

    public function test_pos_sale_stays_paid_until_it_is_handed_over(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $existing = Customer::factory()->create(['mobile' => '09121230004', 'name' => 'مشتری قدیمی']);
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);

        $this->startWithCustomer('۰۹۱۲۱۲۳۰۰۰۴', 'نام دیگر');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [
                [
                    'method' => 'pos',
                    'amount' => $price,
                ],
            ],
            'handover' => '0',
        ])->assertRedirect(route('admin.invoice.create', ['step' => 'review']));
        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->assertSame(Invoice::PAID, $invoice->status);
        $this->assertSame($existing->id, $invoice->customer_id);
        $this->assertSame(1, Customer::query()->where('mobile', '09121230004')->count());

        $payment = $invoice->payments()->firstOrFail();
        $this->assertSame('CARD', $payment->type);
        $this->assertSame('pos', $payment->meta['method']);
        $this->assertSame((int) $invoice->total_price, $invoice->receivedAmount());

        $this->get(route('admin.invoice.edit', $invoice))->assertOk();
        $this->get(route('admin.invoice.show', $invoice))
            ->assertOk()
            ->assertSee(__('In-store payment'));
        $this->get(route('admin.invoice.print', $invoice))
            ->assertOk()
            ->assertSee(__('In-store payment'))
            ->assertSee(__('Received amount'));
    }

    public function test_multi_payment_manual_invoice_with_supplier_and_bank_account(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();
        $bank = BankAccount::factory()->active()->create(['bank_name' => 'Melli']);
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);

        $part1 = (int) floor($price / 2);
        $part2 = $price - $part1;

        $this->startWithCustomer('09121230005', 'خریدار دو پرداختی');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [
                [
                    'method' => 'pos',
                    'amount' => $part1,
                    'supplier_id' => $supplier->id,
                ],
                [
                    'method' => 'card_to_card',
                    'amount' => $part2,
                    'bank_account_id' => $bank->id,
                    'tracking_number' => 'TRK-987654',
                ],
            ],
            'handover' => '1',
        ])->assertRedirect(route('admin.invoice.create', ['step' => 'review']));

        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->assertSame(Invoice::COMPLETED, $invoice->status);
        $this->assertSame(2, $invoice->payments()->count());
        $this->assertSame($price, $invoice->receivedAmount());
        $this->assertSame(0, $invoice->remainingReceiptBalance());

        $firstPayment = $invoice->payments()->where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame($part1, (int) $firstPayment->amount);
        $this->assertSame('pos', $firstPayment->meta['method']);
        $this->assertSame($supplier->id, $firstPayment->supplier->id);
        $this->assertTrue($supplier->payments->contains($firstPayment));

        $secondPayment = $invoice->payments()->whereNull('supplier_id')->firstOrFail();
        $this->assertSame($part2, (int) $secondPayment->amount);
        $this->assertSame('TRK-987654', $secondPayment->reference_id);
        $this->assertSame($bank->id, $secondPayment->meta['bank_account_id']);
    }

    public function test_partial_payment_creates_awaiting_payment_invoice_and_blocks_handover(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);

        $partial = (int) floor($price / 3);

        $this->startWithCustomer('09121230006', 'خریدار بیعانه');
        $this->addPiece($piece);

        $this->step([
            'step' => 'payment',
            'payments' => [
                [
                    'method' => 'pos',
                    'amount' => $partial,
                ],
            ],
            'handover' => '1',
        ])->assertSessionHasErrors('handover');

        $this->step([
            'step' => 'payment',
            'payments' => [
                [
                    'method' => 'pos',
                    'amount' => $partial,
                ],
            ],
            'handover' => '0',
        ])->assertRedirect(route('admin.invoice.create', ['step' => 'review']));

        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame($partial, $invoice->receivedAmount());
        $this->assertSame($price - $partial, $invoice->remainingReceiptBalance());
        $this->assertSame(QuantityPieceStatus::Sold, $piece->fresh()->status);
    }

    public function test_adding_payment_to_existing_unsettled_invoice(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $supplier = Supplier::factory()->create();
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);
        $partial = (int) floor($price / 2);
        $remaining = $price - $partial;

        $this->startWithCustomer('09121230007', 'مشتری تسویه بعدی');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [
                ['method' => 'pos', 'amount' => $partial],
            ],
            'handover' => '0',
        ]);
        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame($remaining, $invoice->remainingReceiptBalance());

        $this->post(route('admin.invoice.add-payment', $invoice), [
            'method' => 'card_to_card',
            'amount' => $remaining,
            'supplier_id' => $supplier->id,
            'tracking_number' => 'ADD-889900',
        ])->assertRedirect(route('admin.invoice.edit', $invoice));

        $invoice->refresh();

        $this->assertSame(Invoice::PAID, $invoice->status);
        $this->assertSame(0, $invoice->remainingReceiptBalance());
        $this->assertSame(2, $invoice->payments()->count());

        $latestPayment = $invoice->payments()->latest('id')->firstOrFail();
        $this->assertSame($remaining, (int) $latestPayment->amount);
        $this->assertSame($supplier->id, $latestPayment->supplier_id);
        $this->assertSame('ADD-889900', $latestPayment->reference_id);
    }

    public function test_total_payments_cannot_exceed_order_total(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);

        $this->startWithCustomer('09121230008');
        $this->addPiece($piece);

        $this->step([
            'step' => 'payment',
            'payments' => [
                ['method' => 'pos', 'amount' => $price + 50000],
            ],
        ])->assertSessionHasErrors('payments');
    }

    public function test_add_payment_cannot_exceed_remaining_balance(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();
        $price = $this->priceOf($piece);
        $partial = (int) floor($price / 2);
        $remaining = $price - $partial;

        $this->startWithCustomer('09121230009');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [
                ['method' => 'pos', 'amount' => $partial],
            ],
            'handover' => '0',
        ]);
        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->post(route('admin.invoice.add-payment', $invoice), [
            'method' => 'pos',
            'amount' => $remaining + 10000,
        ])->assertSessionHasErrors('amount');
    }

    public function test_cash_payment_method_is_rejected(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();

        $this->startWithCustomer('09121230010');
        $this->addPiece($piece);

        $this->step([
            'step' => 'payment',
            'payments' => [
                ['method' => 'cash', 'amount' => 10000],
            ],
        ])->assertSessionHasErrors('payments.0.method');
    }

    public function test_sale_without_immediate_payments_creates_awaiting_payment_invoice(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $bank = BankAccount::factory()->active()->create(['bank_name' => 'Melli']);
        $piece = $this->makePiece();

        $this->startWithCustomer('09121230011', 'خریدار پرداخت مؤخر');
        $this->addPiece($piece);
        $this->step([
            'step' => 'payment',
            'payments' => [],
            'handover' => '0',
        ])->assertRedirect(route('admin.invoice.create', ['step' => 'review']));

        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
        $this->assertSame(QuantityPieceStatus::Sold, $piece->fresh()->status);
        $this->assertSame(0, $invoice->receivedAmount());

        $payment = $invoice->payments()->firstOrFail();
        $this->assertSame('CARD', $payment->type);
        $this->assertSame(Payment::PENDING, $payment->status);
        $this->assertSame($bank->id, $payment->meta['bank_account_id']);
        $this->assertArrayNotHasKey('channel', $payment->meta);
    }

    public function test_a_piece_that_is_already_sold_cannot_be_added(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();
        $piece = $this->makePiece();
        $piece->markSold();

        $this->startWithCustomer('09121230007');

        $this->step(['step' => 'items', 'action' => 'add', 'quantity_ids' => [$piece->id]])
            ->assertSessionHasErrors('quantity_ids');

        $this->assertSame([], app(ManualInvoiceDraft::class)->get()['quantity_ids']);
    }

    public function test_product_below_its_buy_price_cannot_be_sold_in_the_shop(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();
        $piece = $this->makePiece(price: 1_000_000, buyPrice: 5_000_000);

        $this->startWithCustomer('09121230008');

        $this->step(['step' => 'items', 'action' => 'add', 'quantity_ids' => [$piece->id]])
            ->assertSessionHasErrors('quantity_ids');
        $this->assertSame(QuantityPieceStatus::Available, $piece->fresh()->status);
    }

    public function test_piece_sold_elsewhere_before_saving_blocks_the_invoice_and_keeps_the_draft(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();

        $this->startWithCustomer('09121230009');
        $this->addPiece($piece);
        $this->step(['step' => 'payment', 'payment_method' => 'cash']);

        $piece->markSold();

        $this->step(['step' => 'review'])->assertSessionHasErrors('quantity_ids');

        $this->assertSame(0, Invoice::query()->where('source', InvoiceSource::Manual->value)->count());
        $this->get(route('admin.invoice.create', ['step' => 'items']))->assertOk();
    }

    public function test_the_service_refuses_a_sale_without_a_customer_and_saves_nothing(): void
    {
        $this->seedMetalSettings();
        $admin = $this->actingAsAdmin();
        $piece = $this->makePiece();

        $thrown = null;

        try {
            app(ManualInvoiceService::class)->create([
                'customer' => null,
                'quantity_ids' => [$piece->id],
                'payment_method' => 'cash',
                'handover' => false,
                'note' => null,
            ], $admin);
        } catch (ValidationException $exception) {
            $thrown = $exception;
        }

        $this->assertNotNull($thrown);
        $this->assertArrayHasKey('mobile', $thrown->errors());
        $this->assertSame(0, Customer::query()->count());
        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(QuantityPieceStatus::Available, $piece->fresh()->status);
    }

    public function test_discarding_the_sale_clears_the_draft_without_saving_anything(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();

        $this->startWithCustomer('09121230010');
        $this->addPiece($piece);

        $this->step(['step' => 'cancel'])
            ->assertRedirect(route('admin.invoice.create'));

        $this->assertSame(0, Invoice::query()->count());
        $this->assertSame(QuantityPieceStatus::Available, $piece->fresh()->status);
        $this->get(route('admin.invoice.create', ['step' => 'items']))
            ->assertRedirect(route('admin.invoice.create'));
    }

    public function test_invoice_list_can_be_filtered_to_shop_sales(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $checkout = Invoice::factory()->paid()->create();
        $manual = Invoice::factory()->manual()->paid()->create();

        $response = $this->get(route('admin.invoice.index', ['filter' => ['source' => 'MANUAL']]));

        $response->assertOk();
        $response->assertSee(__('Shop sale'));
        $this->assertSame([$manual->id], $response->viewData('items')->pluck('id')->all());

        $this->assertSame(InvoiceSource::Checkout, $checkout->fresh()->source);
        $this->assertTrue($manual->fresh()->isManual());
    }

    public function test_received_amount_counts_card_to_card_receipts_once(): void
    {
        $invoice = Invoice::factory()->manual()->awaitingPayment()->create(['total_price' => 1_000_000]);

        $payment = new Payment;
        $payment->type = 'CARD';
        $payment->status = Payment::SUCCESS;
        $payment->amount = 1_000_000;
        $payment->order_id = 'CARD-TEST-'.uniqid();
        $payment->meta = ['bank_account_id' => 1];
        $invoice->payments()->save($payment);

        PaymentReceipt::factory()->create([
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => 1_000_000,
        ]);

        $fresh = Invoice::query()->with(['payments', 'paymentReceipts'])->findOrFail($invoice->id);

        $this->assertSame(1_000_000, $fresh->receivedAmount());
        $this->assertSame(1_000_000, $invoice->fresh()->receivedAmount());
        $this->assertSame(0, $fresh->remainingReceiptBalance());
    }

    public function test_every_wizard_step_renders_and_the_saved_sale_is_marked_as_a_shop_sale(): void
    {
        $this->withoutVite();
        $this->seedMetalSettings();
        $this->actingAsAdmin();
        $piece = $this->makePiece();
        $other = $this->makePiece();

        $this->startWithCustomer('09121230012', 'صفحه تست');

        $this->get(route('admin.invoice.create', ['step' => 'items', 'q' => $other->code]))
            ->assertOk()
            ->assertSee($other->code)
            ->assertDontSee($piece->code);

        $this->addPiece($piece);

        $this->get(route('admin.invoice.create', ['step' => 'items']))
            ->assertOk()
            ->assertSee($piece->code)
            ->assertSee(__('Selected pieces'));

        $this->step(['step' => 'items', 'action' => 'add', 'quantity_ids' => [$other->id], 'q' => $other->code])
            ->assertRedirect(route('admin.invoice.create', ['step' => 'items', 'q' => $other->code]));

        $totalPrice = $this->priceOf($piece) + $this->priceOf($other);

        $this->get(route('admin.invoice.create', ['step' => 'payment']))
            ->assertOk()
            ->assertSee('value="pos"', false)
            ->assertDontSee('value="cash"', false)
            ->assertSee('id="add-payment-btn"', false)
            ->assertSee('id="payment-rows-container"', false)
            ->assertDontSee('id="payment-row-template"', false)
            ->assertSee('name="handover"', false);

        $this->step([
            'step' => 'payment',
            'payments' => [
                ['method' => 'pos', 'amount' => $totalPrice],
            ],
            'handover' => '1',
        ]);

        $this->get(route('admin.invoice.create', ['step' => 'review']))
            ->assertOk()
            ->assertSee(__('Create invoice'))
            ->assertSee($piece->code);

        $this->step(['step' => 'review']);

        $invoice = Invoice::query()->where('source', InvoiceSource::Manual->value)->firstOrFail();

        $this->get(route('admin.invoice.edit', $invoice))
            ->assertRedirect(route('admin.invoice.show', $invoice));

        $this->get(route('admin.invoice.show', $invoice))
            ->assertOk()
            ->assertSee(__('Shop sale'))
            ->assertSee(__('Created by'));
    }

    public function test_draft_is_kept_in_the_session_under_its_own_key(): void
    {
        $this->withoutVite();
        $this->actingAsAdmin();

        $this->startWithCustomer('09121230011');

        $this->assertSame('09121230011', session(ManualInvoiceDraft::SESSION_KEY)['customer']['mobile']);
    }
}
