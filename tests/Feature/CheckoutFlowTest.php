<?php

namespace Tests\Feature;

use App\Mail\AuthMail;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_index_route_is_public_for_guests(): void
    {
        $route = app('router')->getRoutes()->getByName('client.card');
        $middleware = collect($route->gatherMiddleware());

        $this->assertFalse($middleware->contains('auth:customer'));
    }

    public function test_guest_checkout_is_redirected_to_login(): void
    {
        $response = $this->post(route('client.card.check'), [
            'product_id' => [1],
            'count' => [1],
            'address_id' => 1,
            'transport_id' => 1,
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $this->assertGuest('customer');
    }

    public function test_signup_requires_name_mobile_and_address(): void
    {
        $response = $this->post(route('client.sign-up-now'), [
            'email' => 'buyer@example.com',
        ]);

        $response->assertSessionHasErrors(['name', 'mobile', 'address']);
    }

    public function test_signup_creates_customer_with_address_and_logs_in(): void
    {
        Mail::fake();

        $response = $this->post(route('client.sign-up-now'), [
            'name' => 'خریدار تست',
            'mobile' => '09121234567',
            'email' => 'buyer'.uniqid().'@example.com',
            'address' => 'تهران خیابان تست پلاک ۱۲۳۴۵',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('customer');
        Mail::assertSent(AuthMail::class);

        $customer = auth('customer')->user();
        $this->assertNotNull($customer->name);
        $this->assertNotNull($customer->mobile);
        $this->assertTrue($customer->addresses()->exists());
        $this->assertTrue($customer->isCheckoutReady());
    }

    public function test_ajax_signup_returns_json_for_in_card_auth(): void
    {
        Mail::fake();

        $response = $this->postJson(route('client.sign-up-now'), [
            'name' => 'خریدار تست',
            'mobile' => '09129876543',
            'email' => 'ajaxbuyer'.uniqid().'@example.com',
            'address' => 'تهران خیابان تست پلاک ۱۲۳۴۵',
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonPath('data.profile_complete', true);

        $this->assertAuthenticated('customer');
    }

    public function test_ajax_login_returns_json_for_in_card_auth(): void
    {
        $password = 'secret12';
        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '09121112233',
            'email' => 'login'.uniqid().'@example.com',
            'password' => bcrypt($password),
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان آزادی پلاک ۱۰';
        $address->save();

        $response = $this->postJson(route('client.sign-in-do'), [
            'email' => $customer->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonPath('data.profile_complete', true);

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_complete_checkout_profile_from_card(): void
    {
        $customer = Customer::factory()->create([
            'name' => null,
            'mobile' => null,
            'email' => 'incomplete'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('client.card.complete-profile'), [
            'name' => 'کامل شده',
            'mobile' => '09123334455',
            'address' => 'تهران خیابان ولیعصر پلاک ۱۰۰',
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonPath('data.profile_complete', true);

        $customer->refresh();
        $this->assertSame('کامل شده', $customer->name);
        $this->assertSame('09123334455', $customer->mobile);
        $this->assertTrue($customer->addresses()->exists());
    }

    public function test_first_add_to_card_persists_selected_quantity_cookie(): void
    {
        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => 1_000_000,
            'stock_quantity' => 1,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 1_000_000,
            'code' => 'C-'.uniqid(),
        ]);

        $response = $this->get(route('client.product-card-toggle', $product->slug).'?quantity='.$quantity->id);

        $response->assertRedirect();
        $response->assertCookie('card');
        $response->assertCookie('q', json_encode([$quantity->id]));
    }

    public function test_card_items_include_selected_stock_piece(): void
    {
        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => 900_000,
            'stock_quantity' => 1,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 3.5,
            'count' => 1,
            'price' => 1_250_000,
            'code' => 'C-'.uniqid(),
        ]);

        $this->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]));

        // Simulate request with cookies for helper
        request()->cookies->set('card', json_encode([$product->id]));
        request()->cookies->set('q', json_encode([$quantity->id]));

        $lines = cardItems();

        $this->assertCount(1, $lines);
        $this->assertNotNull($lines[0]['q']);
        $this->assertSame($quantity->id, $lines[0]['q']['id']);
        $this->assertSame(1_250_000, $lines[0]['price']);
        $this->assertSame($quantity->id, $lines[0]['selected_quantity_id']);
    }

    public function test_incomplete_profile_cannot_checkout(): void
    {
        $customer = Customer::factory()->create([
            'name' => null,
            'mobile' => null,
            'email' => 'incomplete'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [1],
            'count' => [1],
            'address_id' => 1,
            'transport_id' => 1,
            'payment_method' => 'card',
        ]);

        $response->assertRedirect(route('client.profile'));
        $response->assertSessionHasErrors();
    }

    public function test_card_to_card_checkout_creates_pending_payment(): void
    {
        BankAccount::factory()->active()->create([
            'bank_name' => 'Melli',
            'account_holder_name' => 'Shop Holder',
            'card_number' => '6037991111222233',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '0912000'.rand(1000, 9999),
            'email' => 'cardpay'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان آزادی پلاک ۱۰';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک';
        $transport->price = 50000;
        $transport->is_default = 1;
        $transport->sort = 1;
        $transport->save();

        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => 1_000_000,
            'stock_quantity' => 1,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 1_000_000,
            'code' => 'C-'.uniqid(),
        ]);

        $quantity = $product->quantities()->first();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'status' => Invoice::AWAITING_PAYMENT,
        ]);
        $this->assertDatabaseHas('payments', [
            'type' => 'CARD',
            'status' => Payment::PENDING,
        ]);

        $payment = Payment::query()->where('type', 'CARD')->latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertSame('Melli', $payment->meta['bank_name'] ?? null);
        $this->assertSame('6037991111222233', $payment->meta['card_number'] ?? null);
        $this->assertSame(0, $quantity->fresh()->count);
    }

    public function test_invoice_page_renders_with_address_without_state_city(): void
    {
        BankAccount::factory()->active()->create();

        $customer = Customer::factory()->create([
            'name' => 'Buyer',
            'mobile' => '09121110000',
            'email' => 'invoice'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان آزادی پلاک ۱۰';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک';
        $transport->price = 50000;
        $transport->is_default = 1;
        $transport->sort = 1;
        $transport->save();

        $product = Product::factory()->create([
            'user_id' => User::factory()->create()->id,
            'category_id' => Category::factory()->create()->id,
            'status' => 1,
            'stock_status' => 'IN_STOCK',
            'price' => 1_000_000,
            'stock_quantity' => 1,
            'sku' => 'SKU-'.uniqid(),
            'slug' => 'p-'.uniqid(),
        ]);

        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2,
            'count' => 1,
            'price' => 1_000_000,
            'code' => 'C-'.uniqid(),
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $invoice = \App\Models\Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $invoice->load(['customer', 'address.state', 'address.city', 'orders.product', 'orders.quantity', 'payments']);

        $html = view('segments.invoice.LianaInvoice.LianaInvoice', [
            'invoice' => $invoice,
            'qr' => new class
            {
                public function render(string $url): string
                {
                    return 'data:image/svg+xml,'.rawurlencode('<svg></svg>');
                }
            },
            'data' => (object) [
                'area_name' => 'invoice',
                'part' => 'LianaInvoice',
            ],
        ])->render();

        $this->assertStringContainsString('تهران خیابان آزادی پلاک ۱۰', $html);
        $this->assertStringNotContainsString('Attempt to read property', $html);
    }
}
