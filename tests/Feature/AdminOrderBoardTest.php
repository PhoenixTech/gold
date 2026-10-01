<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\State;
use App\Models\Transport;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminOrderBoardTest extends TestCase
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

    private function createOrder(
        Customer $customer,
        string $status,
        int $totalPrice = 1000000,
        ?Address $address = null
    ): Invoice {
        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->status = $status;
        $invoice->total_price = $totalPrice;
        $invoice->count = 1;
        $invoice->address_id = $address?->id;
        $invoice->save();

        return $invoice;
    }

    public function test_guest_is_redirected_from_the_order_board(): void
    {
        $this->get(route('admin.order-board.index'))->assertRedirect();
    }

    public function test_admin_can_view_order_board(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();
        $response->assertSee(__('Manager dashboard'));
        $response->assertSee(__('Active orders'));
        $response->assertSee(__('Operational order board'));
        $response->assertDontSee('<code');
    }

    public function test_order_board_shows_active_orders_by_default_and_moves_completed_to_history(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create([
            'name' => 'Sara Hosseini',
            'code' => 'ZK-888',
            'mobile' => '09123456789',
        ]);

        $activeOrder = $this->createOrder($customer, Invoice::AWAITING_PAYMENT, 2500000);
        $completedOrder = $this->createOrder($customer, Invoice::COMPLETED, 4000000);

        // Default view: active orders only
        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();
        $response->assertSee('#'.$activeOrder->hash);
        $response->assertDontSee('#'.$completedOrder->hash);

        // Completed scope: completed orders
        $completedResponse = $this->get(route('admin.order-board.index', ['scope' => 'completed']));
        $completedResponse->assertOk();
        $completedResponse->assertSee('#'.$completedOrder->hash);
        $completedResponse->assertDontSee('#'.$activeOrder->hash);
    }

    public function test_order_board_displays_customer_province_and_expandable_address(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $state = new State;
        $state->name = 'تهران';
        $state->country = 'ایران';
        $state->lat = 35.6892;
        $state->lng = 51.3890;
        $state->save();

        $customer = Customer::factory()->create([
            'name' => 'Alireza Rad',
            'code' => 'ZK-777',
            'mobile' => '09998887766',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->state_id = $state->id;
        $address->address = 'خیابان ولیعصر کوچه شقایق پلاک ۴';
        $address->zip = '1234567890';
        $address->save();

        $order = $this->createOrder($customer, Invoice::PAID, 3000000, $address);

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();
        $response->assertSee('Alireza Rad');
        $response->assertDontSee('data-sort="customer_code"');
        $response->assertSee('تهران');
        $response->assertSee('09998887766');
        $response->assertSee('خیابان ولیعصر کوچه شقایق پلاک ۴');
    }

    public function test_order_board_stage_calculations(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $admin = $this->actingAsAdmin();

        $customer = Customer::factory()->create();

        // 1. Unpaid order
        $unpaidOrder = $this->createOrder($customer, Invoice::AWAITING_PAYMENT, 1000000);

        // 2. Paid & confirmed order with active delivery
        $paidOrder = $this->createOrder($customer, Invoice::PAID, 2000000);
        $courier = User::factory()->create(['role' => 'COURIER']);
        Role::findOrCreate('courier', 'web');
        $courier->assignRole('courier');

        Delivery::create([
            'invoice_id' => $paidOrder->id,
            'courier_id' => $courier->id,
            'code_hash' => 'dummy',
            'status' => DeliveryStatus::Accepted->value,
        ]);

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        // Unpaid row contains unpaid badges
        $response->assertSee(__('Unpaid'));
        $response->assertSee(__('Pending'));
    }

    public function test_order_board_search_filter(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customerA = Customer::factory()->create(['name' => 'Farhad Mehrad', 'code' => 'ZK-111']);
        $customerB = Customer::factory()->create(['name' => 'Babak Khorramdin', 'code' => 'ZK-222']);

        $orderA = $this->createOrder($customerA, Invoice::AWAITING_PAYMENT);
        $orderB = $this->createOrder($customerB, Invoice::AWAITING_PAYMENT);

        $response = $this->get(route('admin.order-board.index', ['q' => 'ZK-111']));
        $response->assertOk();
        $response->assertSee('ZK-111');
        $response->assertDontSee('ZK-222');
    }

    public function test_order_board_records_confirmation_meta_and_renders_stage_detail_cards(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $admin = $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $order = $this->createOrder($customer, Invoice::AWAITING_PAYMENT);

        $payment = new Payment;
        $payment->invoice_id = $order->id;
        $payment->amount = 1000000;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'ORD-'.$order->id;
        $payment->save();

        $order->storeSuccessPayment($payment->id, 'REF-TEST-1', null, $admin);

        $payment->refresh();
        $this->assertEquals($admin->id, $payment->meta['confirmed_by']);
        $this->assertEquals($admin->name, $payment->meta['confirmed_by_name']);
        $this->assertNotEmpty($payment->meta['confirmed_at']);

        // A receipt uploaded by the customer
        Storage::fake('public');
        Storage::disk('public')->put('receipts/test.png', 'fake-image');
        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $order->id;
        $receipt->path = 'receipts/test.png';
        $receipt->original_name = 'bank-receipt.png';
        $receipt->mime = 'image/png';
        $receipt->size = 2048;
        $receipt->uploaded_by_customer_id = $customer->id;
        $receipt->save();

        // A delivered shipment
        $courier = User::factory()->create(['role' => 'COURIER']);
        Role::findOrCreate('courier', 'web');
        $courier->assignRole('courier');

        Delivery::create([
            'invoice_id' => $order->id,
            'courier_id' => $courier->id,
            'code_hash' => 'dummy',
            'status' => DeliveryStatus::Delivered->value,
            'accepted_at' => now(),
            'delivered_at' => now(),
        ]);

        $response = $this->get(route('admin.order-board.index', ['scope' => 'all']));
        $response->assertOk();

        // Stage toggle buttons with data-stage attributes
        $response->assertSee('data-stage="payment"', false);
        $response->assertSee('data-stage="confirm"', false);
        $response->assertSee('data-stage="settle"', false);
        $response->assertSee('data-stage="courier"', false);
        $response->assertSee('data-stage="delivery"', false);

        // Sub-row detail cards
        $response->assertSee('data-subcard="payment"', false);
        $response->assertSee('data-subcard="confirm"', false);
        $response->assertSee('data-subcard="settle"', false);
        $response->assertSee('data-subcard="courier"', false);
        $response->assertSee('data-subcard="delivery"', false);

        // Payment details: uploaded receipt
        $response->assertSee('bank-receipt.png');
        $response->assertSee(__('Customer receipts'));
        $response->assertSee($receipt->url());

        // Confirm details: who and when
        $response->assertSee(__('Confirmed by'));
        $response->assertSee($admin->name);
        $response->assertSee(__('Confirmed at'));

        // Courier details: which courier
        $response->assertSee(__('Courier name'));
        $response->assertSee($courier->name);

        // Delivery details: when delivered
        $response->assertSee(__('Delivered at'));
    }

    public function test_settlement_stage_is_distinct_from_payment_confirmation(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();

        // Paid and confirmed, but the receipts only cover part of the total.
        // The confirmation stage is done; the settlement stage is NOT. They used
        // to share the same predicate, so the board showed two identical columns.
        $order = $this->createOrder($customer, Invoice::PAID, 1_000_000);

        $payment = new Payment;
        $payment->invoice_id = $order->id;
        $payment->order_id = 'CARD-'.$order->hash;
        $payment->type = 'CARD';
        $payment->status = Payment::SUCCESS;
        $payment->amount = 1_000_000;
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $order->id;
        $receipt->path = 'receipts/partial.png';
        $receipt->original_name = 'partial.png';
        $receipt->mime = 'image/png';
        $receipt->size = 1024;
        $receipt->amount = 400_000;
        $receipt->uploaded_by_customer_id = $customer->id;
        $receipt->save();

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        $content = $response->getContent();

        // The two stages must disagree on this row: the payment is confirmed,
        // but the receipts only cover 40% of the total so it is not settled.
        // They used to share one predicate, making the 5th column pure noise.
        $this->assertMatchesRegularExpression(
            '/data-confirm="1"\s+data-settle="0"/',
            $content,
            'Confirmation and settlement must be able to disagree for the same order'
        );

        $response->assertSee(__('Short'), false);

        // The tooltip explains the shortfall, and the settled wording is absent.
        $response->assertSee(__('Receipt covers :received of :total — short by :remaining.', [
            'received' => '400,000',
            'total' => '1,000,000',
            'remaining' => '600,000',
        ]), false);
        $response->assertDontSee(__('Balance fully settled.'));

        // The settlement sub-card breaks the money out.
        $response->assertSee(__('Invoice total'));
        $response->assertSee(__('Received via receipts'));
        $response->assertSee(__('Remaining balance'));
        $response->assertSee('400,000');
        $response->assertSee('600,000');
    }

    public function test_settlement_stage_reports_settled_when_receipts_cover_the_total(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $order = $this->createOrder($customer, Invoice::COMPLETED, 800_000);

        $payment = new Payment;
        $payment->invoice_id = $order->id;
        $payment->order_id = 'CARD-'.$order->hash;
        $payment->type = 'CARD';
        $payment->status = Payment::SUCCESS;
        $payment->amount = 800_000;
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $order->id;
        $receipt->path = 'receipts/full.png';
        $receipt->original_name = 'full.png';
        $receipt->mime = 'image/png';
        $receipt->size = 1024;
        $receipt->amount = 800_000;
        $receipt->uploaded_by_customer_id = $customer->id;
        $receipt->save();

        $response = $this->get(route('admin.order-board.index', ['scope' => 'all']));
        $response->assertOk();

        $this->assertMatchesRegularExpression(
            '/data-confirm="1"\s+data-settle="1"/',
            $response->getContent(),
            'Receipts covering the total should settle the order'
        );
        $response->assertSee(__('Settled'), false);
    }

    public function test_payment_stage_is_three_state_and_never_shows_paid_for_an_unverified_receipt(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();

        // Receipt uploaded but payment not yet confirmed.
        $order = $this->createOrder($customer, Invoice::AWAITING_PAYMENT, 500_000);
        $payment = new Payment;
        $payment->invoice_id = $order->id;
        $payment->order_id = 'CARD-'.$order->hash;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->amount = 500_000;
        $payment->save();

        $receipt = new PaymentReceipt;
        $receipt->payment_id = $payment->id;
        $receipt->invoice_id = $order->id;
        $receipt->path = 'receipts/x.png';
        $receipt->original_name = 'x.png';
        $receipt->mime = 'image/png';
        $receipt->size = 10;
        $receipt->uploaded_by_customer_id = $customer->id;
        $receipt->save();

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        $content = $response->getContent();

        // The payment stage is explicitly "receipt uploaded", not "Paid": the row
        // carries neither a done-payment nor a done-confirmation flag.
        $this->assertMatchesRegularExpression(
            '/data-payment="0"\s+data-confirm="0"/',
            $content,
            'An unverified receipt must not mark payment as done'
        );

        $response->assertSee(__('Receipt uploaded'), false);

        // The "receipt uploaded" label must appear in the payment cell rather
        // than the green "Paid" one, which is only reachable when done=true.
        $paymentCell = $this->stageCell($content, 'payment');
        $this->assertStringContainsString(__('Receipt uploaded'), $paymentCell);
        $this->assertStringNotContainsString(__('Paid'), $paymentCell);
    }

    public function test_postal_shipments_are_not_labelled_pending_courier(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();

        // A postal transport never creates a Delivery row, so the board used to
        // show "Pending courier" for these orders forever.
        $transport = new Transport;
        $transport->title = 'Post Express';
        $transport->price = 45000;
        $transport->requires_delivery_code = false;
        $transport->save();

        $postal = $this->createOrder($customer, Invoice::PAID, 900_000);
        $postal->transport_id = $transport->id;
        $postal->save();

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        $content = $response->getContent();

        // A postal shipment never gets a Delivery row, so the board used to say
        // "Pending courier" for these orders forever.
        $courierCell = $this->stageCell($content, 'courier');
        $this->assertStringContainsString(
            __('Pending dispatch'),
            $courierCell,
            'A postal shipment should read "Pending dispatch", not "Pending courier"'
        );
        $this->assertStringNotContainsString(__('Pending courier'), $courierCell);

        $this->assertStringContainsString(__('Postal service'), $content);
        $this->assertStringContainsString(__('This order ships via the postal service and needs no courier or delivery PIN.'), $content);
    }

    public function test_gift_orders_surface_the_recipient_on_the_board(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $buyer = Customer::factory()->create(['name' => 'Buyer Person', 'mobile' => '09120000000']);
        $gift = $this->createOrder($buyer, Invoice::PAID, 1200000);
        $gift->is_third_party = true;
        $gift->recipient_name = 'Maryam Recipient';
        $gift->recipient_mobile = '09350000000';
        $gift->save();

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        $response->assertSee('Maryam Recipient', false);
        $response->assertSee('09350000000', false);
        $response->assertSee(__('Recipient (gift order)'), false);
    }

    public function test_scope_counts_respect_the_active_search(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $wanted = Customer::factory()->create(['name' => 'Zebra Person']);
        $this->createOrder($wanted, Invoice::PAID);

        $hidden = Customer::factory()->create(['name' => 'Completely Different']);
        $this->createOrder($hidden, Invoice::PAID);

        $all = $this->get(route('admin.order-board.index', ['scope' => 'all']));
        $all->assertOk();

        $filtered = $this->get(route('admin.order-board.index', ['scope' => 'all', 'q' => 'Zebra']));
        $filtered->assertOk();
        $filtered->assertSee('#'.$wanted->invoices()->firstOrFail()->hash, false);

        // With a search applied the "all orders" badge must reflect the search,
        // not the unfiltered table.
        $this->assertLessThan(
            (int) $this->badgeValue($all->getContent(), __('All orders count')),
            (int) $this->badgeValue($filtered->getContent(), __('All orders count')),
            'Scope badges should shrink when a search narrows the result set'
        );
    }

    public function test_order_board_paginates_instead_of_rendering_every_row(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        Invoice::factory()->count(18)->create(['customer_id' => $customer->id, 'status' => Invoice::PAID]);

        $response = $this->get(route('admin.order-board.index', ['per_page' => 10]));
        $response->assertOk();

        $this->assertCount(10, $response->viewData('orders'));
        $this->assertSame(18, $response->viewData('orders')->total());
        $this->assertSame(10, $response->viewData('perPage'));

        // Second page holds the remainder.
        $page2 = $this->get(route('admin.order-board.index', ['per_page' => 10, 'page' => 2]));
        $page2->assertOk();
        $this->assertCount(8, $page2->viewData('orders'));

        $response->assertSee(__('Rows per page'), false);
    }

    public function test_order_board_does_not_leak_the_pickup_flag_between_sub_cards(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $courier = User::factory()->create(['role' => 'COURIER']);
        Role::findOrCreate('courier', 'web');
        $courier->assignRole('courier');

        $transport = new Transport;
        $transport->title = 'Motorcycle Courier';
        $transport->price = 50000;
        $transport->requires_delivery_code = true;
        $transport->save();

        // A courier (non-pickup) order rendered after a pickup one used to pick
        // up the previous row's $isPickup variable from Blade's shared scope.
        Invoice::factory()->pickup()->create(['customer_id' => $customer->id]);
        $courierOrder = $this->createOrder($customer, Invoice::OUT_FOR_DELIVERY);
        $courierOrder->transport_id = $transport->id;
        $courierOrder->save();

        Delivery::create([
            'invoice_id' => $courierOrder->id,
            'courier_id' => $courier->id,
            'code_hash' => 'x',
            'status' => DeliveryStatus::Accepted->value,
        ]);

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        // Each courier card must label itself from its own row.
        $this->assertSame(
            1,
            substr_count($response->getContent(), __('This is a store pickup order. No courier assignment is required.')),
            'Exactly the pickup order should show the pickup explanation'
        );
        $this->assertStringContainsString($courier->name, $response->getContent());
    }

    public function test_board_rows_sort_on_the_real_date_not_the_invoice_id(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create();
        $older = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::PAID,
            'created_at' => now()->subDays(10),
        ]);
        $newer = Invoice::factory()->create([
            'customer_id' => $customer->id,
            'status' => Invoice::PAID,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();

        $this->assertStringContainsString(
            'data-date="'.$newer->created_at->timestamp.'"',
            $response->getContent(),
            'The sortable date attribute must carry the invoice timestamp, not its id'
        );
        $this->assertStringNotContainsString('data-date="'.$older->id.'"', $response->getContent());
    }

    /**
     * The rendered text of one workflow-stage cell.
     */
    private function stageCell(string $content, string $stage): string
    {
        preg_match('/data-stage="'.preg_quote($stage, '/').'".*?<span>(.*?)<\/span>/s', $content, $matches);

        return (string) ($matches[1] ?? '');
    }

    private function badgeValue(string $content, string $title): string
    {
        // The scope tab renders: title="<title>">{{ number_format($count) }}
        preg_match('/'.preg_quote($title, '/').'[^>]*>(.*?)<\/span>/s', $content, $matches);

        return preg_replace('/[^0-9]/', '', $matches[1] ?? '0');
    }

    public function test_order_board_displays_customer_name_near_phone_button(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create(['name' => 'Sara Mohammadi']);
        $this->createOrder($customer, Invoice::PROCESSING);

        $response = $this->get(route('admin.order-board.index'));
        $response->assertOk();
        $response->assertSee('Sara Mohammadi');
        $response->assertSee('phone-toggle-btn');
    }
}
