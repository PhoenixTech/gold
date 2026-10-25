<?php

namespace Tests\Feature;

use App\Enums\InvoiceSource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminPaymentListTest extends TestCase
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

    private function createInvoice(string $source = 'CHECKOUT'): Invoice
    {
        $customer = Customer::factory()->create([
            'name' => 'John Doe',
            'mobile' => '0912'.rand(1000000, 9999999),
        ]);

        return Invoice::factory()->paid()->create([
            'customer_id' => $customer->id,
            'source' => $source,
            'total_price' => 1000000,
            'count' => 1,
            'hash' => 'INV'.uniqid(),
        ]);
    }

    private function createPayment(Invoice $invoice, string $status = Payment::SUCCESS, array $overrides = []): Payment
    {
        $payment = new Payment;
        $payment->invoice_id = $invoice->id;
        $payment->status = $status;
        $payment->type = $overrides['type'] ?? 'CARD';
        $payment->amount = $overrides['amount'] ?? 500000;
        $payment->order_id = $overrides['order_id'] ?? 'ORD-'.uniqid();
        $payment->reference_id = $overrides['reference_id'] ?? 'REF-'.uniqid();
        $payment->supplier_id = $overrides['supplier_id'] ?? null;
        $payment->meta = $overrides['meta'] ?? null;

        if (isset($overrides['created_at'])) {
            $payment->created_at = $overrides['created_at'];
        }

        $payment->save();

        return $payment;
    }

    public function test_admin_with_invoice_permission_can_view_payments_list(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createInvoice();
        $this->createPayment($invoice, Payment::SUCCESS, ['amount' => 750000]);

        $response = $this->get(route('admin.payment.index'));

        $response->assertOk();
        $response->assertSee(__('Payments list'));
        $response->assertSee(number_format(750000));
        $response->assertSee(route('admin.payment.index'));
    }

    public function test_guest_is_redirected_and_unauthorized_user_is_forbidden(): void
    {
        $response = $this->get(route('admin.payment.index'));
        $response->assertRedirect(route('login'));

        $user = User::factory()->create(['role' => 'USER']);
        $this->actingAs($user);

        $response = $this->get(route('admin.payment.index'));
        $response->assertForbidden();
    }

    public function test_list_only_includes_successful_payments(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createInvoice();
        $success = $this->createPayment($invoice, Payment::SUCCESS, ['order_id' => 'SUCCESS_ORDER_123', 'amount' => 123000]);
        $pending = $this->createPayment($invoice, Payment::PENDING, ['order_id' => 'PENDING_ORDER_456', 'amount' => 456000]);
        $failed = $this->createPayment($invoice, Payment::FAIL, ['order_id' => 'FAIL_ORDER_789', 'amount' => 789000]);

        $response = $this->get(route('admin.payment.index'));

        $response->assertOk();
        $response->assertSee('SUCCESS_ORDER_123');
        $response->assertSee(number_format(123000));
        $response->assertDontSee('PENDING_ORDER_456');
        $response->assertDontSee('FAIL_ORDER_789');
    }

    public function test_filtering_by_type_physical_and_online(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $manualInvoice = $this->createInvoice(InvoiceSource::Manual->value);
        $physicalPayment = $this->createPayment($manualInvoice, Payment::SUCCESS, [
            'order_id' => 'PHYSICAL_PAY_1',
            'meta' => ['channel' => Payment::CHANNEL_IN_STORE, 'method' => 'pos'],
        ]);

        $onlineInvoice = $this->createInvoice(InvoiceSource::Checkout->value);
        $onlinePayment = $this->createPayment($onlineInvoice, Payment::SUCCESS, [
            'order_id' => 'ONLINE_PAY_2',
            'type' => 'ONLINE',
        ]);

        $responsePhysical = $this->get(route('admin.payment.index', ['type' => 'physical']));
        $responsePhysical->assertOk();
        $responsePhysical->assertSee('PHYSICAL_PAY_1');
        $responsePhysical->assertDontSee('ONLINE_PAY_2');

        $responseOnline = $this->get(route('admin.payment.index', ['type' => 'online']));
        $responseOnline->assertOk();
        $responseOnline->assertSee('ONLINE_PAY_2');
        $responseOnline->assertDontSee('PHYSICAL_PAY_1');
    }

    public function test_filtering_by_supplier(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $supplierA = Supplier::create([
            'first_name' => 'SupplierAlpha',
            'last_name' => 'Karimi',
            'company_name' => 'Alpha Co',
        ]);

        $supplierB = Supplier::create([
            'first_name' => 'SupplierBeta',
            'last_name' => 'Tehrani',
            'company_name' => 'Beta Co',
        ]);

        $invoice = $this->createInvoice();
        $payA = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_SUPPLIER_A',
            'supplier_id' => $supplierA->id,
        ]);
        $payB = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_SUPPLIER_B',
            'supplier_id' => $supplierB->id,
        ]);
        $payStore = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_DIRECT_STORE',
            'supplier_id' => null,
        ]);

        $responseSpecific = $this->get(route('admin.payment.index', ['supplier' => $supplierA->id]));
        $responseSpecific->assertOk();
        $responseSpecific->assertSee('PAY_SUPPLIER_A');
        $responseSpecific->assertDontSee('PAY_SUPPLIER_B');
        $responseSpecific->assertDontSee('PAY_DIRECT_STORE');

        $responseNone = $this->get(route('admin.payment.index', ['supplier' => 'none']));
        $responseNone->assertOk();
        $responseNone->assertSee('PAY_DIRECT_STORE');
        $responseNone->assertDontSee('PAY_SUPPLIER_A');
        $responseNone->assertDontSee('PAY_SUPPLIER_B');

        $responseAny = $this->get(route('admin.payment.index', ['supplier' => 'any']));
        $responseAny->assertOk();
        $responseAny->assertSee('PAY_SUPPLIER_A');
        $responseAny->assertSee('PAY_SUPPLIER_B');
        $responseAny->assertDontSee('PAY_DIRECT_STORE');
    }

    public function test_filtering_by_time_frame(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createInvoice();

        $todayPay = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_TODAY',
            'created_at' => now('Asia/Tehran')->startOfDay()->addHours(2),
        ]);

        $oldPay = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_10_DAYS_AGO',
            'created_at' => now('Asia/Tehran')->subDays(10),
        ]);

        $responseToday = $this->get(route('admin.payment.index', ['time_frame' => 'today']));
        $responseToday->assertOk();
        $responseToday->assertSee('PAY_TODAY');
        $responseToday->assertDontSee('PAY_10_DAYS_AGO');

        $responseAll = $this->get(route('admin.payment.index', ['time_frame' => 'all']));
        $responseAll->assertOk();
        $responseAll->assertSee('PAY_TODAY');
        $responseAll->assertSee('PAY_10_DAYS_AGO');
    }

    public function test_search_by_order_id_or_tracking_code(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createInvoice();

        $payMatch = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'TARGET_MATCH_555',
            'reference_id' => 'TRACK_UNIQUE_999',
        ]);

        $payOther = $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'OTHER_UNMATCHED_111',
            'reference_id' => 'TRACK_OTHER_222',
        ]);

        $response = $this->get(route('admin.payment.index', ['q' => 'TRACK_UNIQUE_999']));
        $response->assertOk();
        $response->assertSee('TARGET_MATCH_555');
        $response->assertDontSee('OTHER_UNMATCHED_111');
    }

    public function test_kpis_calculation_and_quick_chips(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $manualInvoice = $this->createInvoice(InvoiceSource::Manual->value);
        $onlineInvoice = $this->createInvoice(InvoiceSource::Checkout->value);

        $supplier = Supplier::create([
            'first_name' => 'KpiSupplier',
            'last_name' => 'Rezaei',
        ]);

        $this->createPayment($manualInvoice, Payment::SUCCESS, [
            'amount' => 200000,
            'supplier_id' => $supplier->id,
            'meta' => ['channel' => Payment::CHANNEL_IN_STORE],
        ]);

        $this->createPayment($onlineInvoice, Payment::SUCCESS, [
            'amount' => 300000,
            'supplier_id' => null,
            'type' => 'ONLINE',
        ]);

        $response = $this->get(route('admin.payment.index'));
        $response->assertOk();
        $response->assertSee(number_format(500000));
        $response->assertSee(number_format(200000));
        $response->assertSee(number_format(300000));
    }

    public function test_payments_with_trashed_invoices_are_not_included(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $invoice = $this->createInvoice();
        $this->createPayment($invoice, Payment::SUCCESS, [
            'order_id' => 'PAY_WITH_DELETED_INVOICE',
        ]);

        $invoice->delete();
        $this->assertTrue($invoice->fresh()->trashed());

        $response = $this->get(route('admin.payment.index'));
        $response->assertOk();
        $response->assertDontSee('PAY_WITH_DELETED_INVOICE');
    }

    public function test_trashed_suppliers_are_excluded_from_supplier_filter_options(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        Supplier::create([
            'first_name' => 'Active',
            'last_name' => 'Supplier',
        ]);

        $deletedSupplier = Supplier::create([
            'first_name' => 'Deleted',
            'last_name' => 'Supplier',
        ]);
        $deletedSupplier->delete();

        $response = $this->get(route('admin.payment.index'));
        $response->assertOk();
        $response->assertSee('Active Supplier');
        $response->assertDontSee('Deleted Supplier');
    }

    public function test_quick_filter_links_are_mutually_exclusive(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $response = $this->get(route('admin.payment.index', ['type' => 'physical']));
        $response->assertOk();

        $content = $response->getContent();
        $this->assertStringContainsString('type=online', $content);
        $this->assertStringNotContainsString('type=physical&amp;type=online', $content);
        $this->assertStringNotContainsString('type=physical&amp;supplier=any', $content);
    }
}
