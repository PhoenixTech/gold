<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Setting;
use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
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

    private function makeInvoice(Customer $customer, string $status, int $totalPrice): Invoice
    {
        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->status = $status;
        $invoice->total_price = $totalPrice;
        $invoice->count = 1;
        $invoice->save();

        return $invoice;
    }

    public function test_guest_is_redirected_from_the_dashboard(): void
    {
        $this->get(route('home'))->assertRedirect();
        $this->get(route('admin.summary.index'))->assertRedirect();
    }

    public function test_summary_page_shows_overall_settings_sales_and_inventory(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $customer = Customer::factory()->create(['name' => 'Summary Buyer']);
        $product = Product::factory()->create([
            'metal_type' => 'gold',
            'weight' => 1.5,
        ]);

        $paidInvoice = $this->makeInvoice($customer, Invoice::PAID, 5_000_000);
        $paidInvoice->count = 2;
        $paidInvoice->save();

        $paidOrder = new Order;
        $paidOrder->invoice_id = $paidInvoice->id;
        $paidOrder->product_id = $product->id;
        $paidOrder->count = 2;
        $paidOrder->price_total = 5_000_000;
        $paidOrder->save();

        $processingInvoice = $this->makeInvoice($customer, Invoice::PROCESSING, 2_000_000);
        $processingInvoice->count = 1;
        $processingInvoice->save();

        $canceledInvoice = $this->makeInvoice($customer, Invoice::CANCELED, 100_000_000);
        $canceledInvoice->count = 10;
        $canceledInvoice->save();

        foreach ([
            'gold' => ['value' => '8500000', 'raw' => '8111111'],
            'gold24' => ['value' => '9200000', 'raw' => '9222222'],
            'silver' => ['value' => '120000', 'raw' => '113333'],
            'dollar' => ['value' => '61000', 'raw' => '64444'],
            'min' => ['value' => '105', 'raw' => '105'],
            'offline_payment_hours' => ['value' => '3', 'raw' => '3'],
            'cart_quote_minutes' => ['value' => '30', 'raw' => '30'],
        ] as $key => $values) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                array_merge([
                    'section' => 'General',
                    'type' => 'TEXT',
                    'title' => $key,
                    'active' => true,
                    'ltr' => true,
                ], $values)
            );
        }

        $response = $this->get(route('admin.summary.index'));

        $response->assertOk();
        $response->assertSee('shop-summary', false);
        $response->assertSee(__('Shop summary'), false);
        $response->assertSee(__('Overall sales'), false);
        $response->assertSee(__('Shop settings'), false);
        $response->assertSee(__('Inventory overview'), false);
        $response->assertSee(__('Cart quote duration'), false);
        $response->assertSee(__('Summaries'), false);
        $response->assertSee(number_format(7_000_000), false);
        $response->assertSee(number_format(3), false);
        $response->assertSee(number_format(2), false);
        $response->assertSee(\App\Services\AdminDashboardStats::formatWeight(3.0), false);
        $response->assertSee(number_format(9_200_000), false);
        $response->assertSee(number_format(105), false);
        $response->assertDontSee(number_format(8_111_111), false);
        $response->assertDontSee(number_format(9_222_222), false);
        $response->assertDontSee(number_format(113_333), false);
        $response->assertDontSee(number_format(64_444), false);
        $response->assertSee(__('Selling price must be at least :percent% of purchase price.', [
            'percent' => number_format(105),
        ]), false);
        $response->assertSee('Summary Buyer', false);
        $response->assertDontSee(number_format(100_000_000), false);
    }

    public function test_dashboard_shows_market_rates_with_update_times(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $goldUpdatedAt = now()->subMinutes(25);
        $gold = Setting::factory()->create([
            'key' => 'gold',
            'title' => 'Gold price',
            'type' => 'TEXT',
            'ltr' => true,
            'value' => '8500000',
            'raw' => '8111111',
        ]);
        $gold->timestamps = false;
        $gold->updated_at = $goldUpdatedAt;
        $gold->save();

        Setting::factory()->create([
            'key' => 'silver',
            'title' => 'Silver price',
            'type' => 'TEXT',
            'ltr' => true,
            'value' => '120000',
            'raw' => '113333',
        ]);
        Setting::factory()->create([
            'key' => 'dollar',
            'title' => 'Dollar price',
            'type' => 'TEXT',
            'ltr' => true,
            'value' => '61000',
            'raw' => '64444',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('shop-dashboard', false);
        $response->assertSee('dash-rate--gold', false);
        $response->assertSee(number_format(8500000), false);
        $response->assertSee(number_format(120000), false);
        $response->assertSee(number_format(61000), false);
        $response->assertDontSee(number_format(8_111_111), false);
        $response->assertDontSee(number_format(113_333), false);
        $response->assertDontSee(number_format(64_444), false);
        $response->assertSee(Invoice::formatPersianDateTime($goldUpdatedAt), false);
        $response->assertSee(__('Gold 18K Price'), false);
        $response->assertSee(__('Silver price'), false);
        $response->assertSee(__('Dollar Rate'), false);
        $response->assertSee(__('Updated: :time', [
            'time' => Invoice::formatPersianDateTime($goldUpdatedAt),
        ]), false);
    }

    public function test_dashboard_shows_shop_action_counts_and_recent_invoices(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $waitingCustomer = Customer::factory()->create(['name' => 'Waiting Receipt Buyer']);
        $confirmCustomer = Customer::factory()->create(['name' => 'Waiting Confirmation Buyer']);
        $paidCustomer = Customer::factory()->create(['name' => 'Paid Order Buyer']);

        $this->makeInvoice($waitingCustomer, Invoice::AWAITING_PAYMENT, 1_000_000);
        $this->makeInvoice($waitingCustomer, Invoice::AWAITING_PAYMENT, 1_500_000);

        $confirmInvoice = $this->makeInvoice($confirmCustomer, Invoice::AWAITING_PAYMENT, 2_000_000);
        $payment = new Payment;
        $payment->invoice_id = $confirmInvoice->id;
        $payment->amount = 2_000_000;
        $payment->type = 'CARD';
        $payment->status = Payment::PENDING;
        $payment->order_id = 'dash-'.uniqid();
        $payment->save();
        PaymentReceipt::factory()->create([
            'payment_id' => $payment->id,
            'invoice_id' => $confirmInvoice->id,
            'uploaded_by_customer_id' => $confirmCustomer->id,
        ]);

        $this->makeInvoice($paidCustomer, Invoice::PAID, 3_400_000);

        Product::factory()->count(2)->create(['metal_type' => 'gold']);
        Product::factory()->create(['metal_type' => 'silver']);
        BankAccount::factory()->active()->create([
            'bank_name' => 'Melli',
            'account_holder_name' => 'Zhonella Shop',
        ]);

        $ticket = new Ticket;
        $ticket->title = 'Need help with an order';
        $ticket->body = 'When will my ring ship?';
        $ticket->customer_id = $paidCustomer->id;
        $ticket->status = 'PENDING';
        $ticket->save();

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee(__('WAITING_RECEIPT'), false);
        $response->assertSee(__('WAITING_CONFIRMATION'), false);
        $response->assertSee(__('Need process orders'), false);
        $response->assertSee(__('Pending tickets'), false);
        $response->assertSee(__('This month sales'), false);
        $response->assertSee(__('Active bank account'), false);
        $response->assertSee(__('Recent invoices'), false);
        $response->assertSee(__('Quick access'), false);
        $response->assertSee('Waiting Receipt Buyer', false);
        $response->assertSee('Waiting Confirmation Buyer', false);
        $response->assertSee('Paid Order Buyer', false);
        $response->assertSee('Melli', false);
        $response->assertSee('Zhonella Shop', false);
        $response->assertSee(number_format(3_400_000), false);
        $response->assertSee(route('admin.invoice.index', [
            'filter' => ['status' => Invoice::WAITING_RECEIPT],
        ]), false);
    }

    public function test_dashboard_shows_in_stock_and_sold_pieces_and_weights_breakdown(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();

        $goldProduct = Product::factory()->create(['metal_type' => 'gold']);
        $silverProduct = Product::factory()->create(['metal_type' => 'silver']);

        // Gold product: 10 available pieces of 2.0g each (total 20g), 5 sold pieces of 3.0g each (total 15g)
        for ($i = 0; $i < 10; $i++) {
            Quantity::create([
                'product_id' => $goldProduct->id,
                'weight' => 2.000,
                'count' => 1,
                'price' => 1000000,
            ]);
        }
        for ($i = 0; $i < 5; $i++) {
            Quantity::create([
                'product_id' => $goldProduct->id,
                'weight' => 3.000,
                'count' => 0,
                'price' => 1500000,
            ]);
        }

        // Silver product: 10 available pieces of 2.0g each (total 20g), 4 sold pieces of 2.5g each (total 10g)
        for ($i = 0; $i < 10; $i++) {
            Quantity::create([
                'product_id' => $silverProduct->id,
                'weight' => 2.000,
                'count' => 1,
                'price' => 200000,
            ]);
        }
        for ($i = 0; $i < 4; $i++) {
            Quantity::create([
                'product_id' => $silverProduct->id,
                'weight' => 2.500,
                'count' => 0,
                'price' => 250000,
            ]);
        }

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('card-stock', false);
        $response->assertSee('card-sold', false);

        // In-stock card
        $response->assertSee(__('In-stock inventory'), false);
        // Total: 20 pieces, 40 grams
        $response->assertSee('20', false);
        $response->assertSee('40', false);

        // Sold card
        $response->assertSee(__('Sold items'), false);
        // Total sold: 9 pieces, 25 grams
        $response->assertSee('9', false);
        $response->assertSee('25', false);
    }

    public function test_dashboard_uses_persian_labels_for_shop_widgets(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        App::setLocale('fa');

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('قیمت طلا ۱۸ عیار', false);
        $response->assertSee('قیمت نقره', false);
        $response->assertSee('نرخ دلار', false);
        $response->assertSee('در انتظار رسید', false);
        $response->assertSee('در انتظار تایید', false);
        $response->assertSee('فروش این ماه', false);
        $response->assertSee('حساب بانکی فعال', false);
        $response->assertSee('آخرین صورت‌حساب‌ها', false);
        $response->assertSee('دسترسی سریع', false);
        $response->assertSee('هنوز به‌روز نشده', false);
        $response->assertSee('موجودی انبار', false);
        $response->assertSee('محصولات فروخته‌شده', false);
    }
}
