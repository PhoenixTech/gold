<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Customer;
use App\Models\Invoice;
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
            'raw' => '8500000',
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
            'raw' => '120000',
        ]);
        Setting::factory()->create([
            'key' => 'dollar',
            'title' => 'Dollar price',
            'type' => 'TEXT',
            'ltr' => true,
            'value' => '61000',
            'raw' => '61000',
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('shop-dashboard', false);
        $response->assertSee('dash-rate--gold', false);
        $response->assertSee(number_format(8500000), false);
        $response->assertSee(number_format(120000), false);
        $response->assertSee(number_format(61000), false);
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
