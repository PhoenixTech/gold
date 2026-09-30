<?php

namespace Tests\Feature;

use App\Mail\AuthMail;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\City;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\State;
use App\Models\Transport;
use App\Models\User;
use App\Services\ProductPriceCalculator;
use Database\Seeders\GfxSeeder;
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
        $this->assertSame(
            app(ProductPriceCalculator::class)->priceForQuantity($product, $quantity),
            $lines[0]['price']
        );
        $this->assertSame($quantity->id, $lines[0]['selected_quantity_id']);

        $this->seed(GfxSeeder::class);

        $response = $this->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]))
            ->get(route('client.card'));

        $response->assertOk();
        $response->assertSee('ns-card', false);
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

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $response->assertRedirect(route('client.invoice.receipt', $invoice->hash));
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
        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $invoice->load(['customer', 'address.state', 'address.city', 'orders.product', 'orders.quantity', 'payments']);

        $html = view('client.customer.invoice', [
            'invoice' => $invoice,
            'qr' => new class
            {
                public function render(string $url): string
                {
                    return 'data:image/svg+xml,'.rawurlencode('<svg></svg>');
                }
            },
        ])->render();

        $this->assertStringContainsString('تهران خیابان آزادی پلاک ۱۰', $html);
        $this->assertStringNotContainsString('Attempt to read property', $html);
    }

    public function test_card_view_payload_contains_5_step_workflow_translations(): void
    {
        $this->seed(GfxSeeder::class);

        $customer = Customer::factory()->create([
            'name' => 'خریدار نمونه',
            'mobile' => '09121113344',
        ]);

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

        $response = $this->actingAs($customer, 'customer')
            ->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]))
            ->get(route('client.card'));

        $response->assertOk();
        $response->assertSee('ns-card', false);

        preg_match('/payload-b64="([^"]+)"/', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);

        $payload = json_decode(base64_decode($matches[1]), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('translate', $payload);

        $translations = $payload['translate'];
        $this->assertArrayHasKey('delivery-type', $translations);
        $this->assertArrayHasKey('delivery-details', $translations);
        $this->assertArrayHasKey('invoice-details', $translations);
        $this->assertArrayHasKey('payment-details', $translations);
        $this->assertArrayHasKey('continue-to-payment', $translations);

        $this->assertSame('روش تحویل', $translations['delivery-type']);
        $this->assertSame('مشخصات تحویل گیرنده', $translations['delivery-details']);
        $this->assertSame('جزئیات فاکتور', $translations['invoice-details']);
        $this->assertSame('اطلاعات پرداخت', $translations['payment-details']);
        $this->assertSame('ادامه به پرداخت', $translations['continue-to-payment']);
        $this->assertArrayHasKey('state', $translations);
        $this->assertArrayHasKey('city', $translations);
        $this->assertArrayHasKey('select-state', $translations);
        $this->assertArrayHasKey('select-city', $translations);
        $this->assertArrayHasKey('states', $payload);
        $this->assertArrayHasKey('stateLink', $payload);
        $this->assertArrayHasKey('citiesLink', $payload);
    }

    public function test_complete_checkout_profile_saves_state_and_city_and_zip(): void
    {
        $customer = Customer::factory()->create([
            'name' => null,
            'mobile' => null,
            'email' => 'withaddress'.uniqid().'@example.com',
        ]);

        $state = new State;
        $state->setTranslation('name', 'fa', 'تهران');
        $state->setTranslation('country', 'fa', 'ایران');
        $state->lat = '35.6892';
        $state->lng = '51.3890';
        $state->save();

        $city = new City;
        $city->state_id = $state->id;
        $city->setTranslation('name', 'fa', 'تهران');
        $city->save();

        $response = $this->actingAs($customer, 'customer')->postJson(route('client.card.complete-profile'), [
            'name' => 'رضا علوی',
            'mobile' => '09121112233',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'zip' => '1234567890',
            'address' => 'خیابان انقلاب، پلاک ۲۰',
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonPath('data.profile_complete', true)
            ->assertJsonPath('data.customer.name', 'رضا علوی')
            ->assertJsonPath('data.customer.mobile', '09121112233');

        $this->assertDatabaseHas('addresses', [
            'customer_id' => $customer->id,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'zip' => '1234567890',
            'address' => 'خیابان انقلاب، پلاک ۲۰',
        ]);

        $customer->refresh();
        $this->assertSame('رضا علوی', $customer->name);
        $this->assertSame('09121112233', $customer->mobile);
        $this->assertTrue($customer->addresses()->exists());
        $savedAddress = $customer->addresses()->first();
        $this->assertTrue($savedAddress->is_tehran);
    }

    public function test_state_and_city_serialize_name_as_translated_string(): void
    {
        $state = new State;
        $state->setTranslation('name', 'fa', 'اصفهان');
        $state->setTranslation('country', 'fa', 'ایران');
        $state->lat = '32.6546';
        $state->lng = '51.6680';
        $state->save();

        $city = new City;
        $city->state_id = $state->id;
        $city->setTranslation('name', 'fa', 'کاشان');
        $city->lat = '33.9850';
        $city->lng = '51.4100';
        $city->save();

        $stateArray = $state->toArray();
        $this->assertIsString($stateArray['name']);
        $this->assertSame('اصفهان', $stateArray['name']);

        $cityArray = $city->toArray();
        $this->assertIsString($cityArray['name']);
        $this->assertSame('کاشان', $cityArray['name']);
    }

    public function test_complete_checkout_profile_adds_second_address_when_address_is_new(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'علی تهرانی',
            'mobile' => '09129998877',
            'email' => 'multiaddr'.uniqid().'@example.com',
        ]);

        $state1 = new State;
        $state1->setTranslation('name', 'fa', 'اصفهان');
        $state1->setTranslation('country', 'fa', 'ایران');
        $state1->lat = '32.6546';
        $state1->lng = '51.6680';
        $state1->save();

        $existingAddress = new Address;
        $existingAddress->customer_id = $customer->id;
        $existingAddress->state_id = $state1->id;
        $existingAddress->address = 'اصفهان، میدان نقش جهان';
        $existingAddress->save();

        $state2 = new State;
        $state2->setTranslation('name', 'fa', 'تهران');
        $state2->setTranslation('country', 'fa', 'ایران');
        $state2->lat = '35.6892';
        $state2->lng = '51.3890';
        $state2->save();

        $response = $this->actingAs($customer, 'customer')->postJson(route('client.card.complete-profile'), [
            'name' => 'علی تهرانی',
            'mobile' => '09129998877',
            'state_id' => $state2->id,
            'address' => 'تهران، سعادت‌آباد، خیابان یکم',
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonCount(2, 'data.addresses');

        $this->assertSame(2, $customer->addresses()->count());
    }

    public function test_complete_checkout_profile_preserves_existing_verified_mobile(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'سعید رضایی',
            'mobile' => '09121112233',
            'email' => 'lockedmobile'.uniqid().'@example.com',
        ]);

        $response = $this->actingAs($customer, 'customer')->postJson(route('client.card.complete-profile'), [
            'name' => 'سعید رضایی جدید',
            'mobile' => '09129990000',
            'for_pickup' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('OK', true)
            ->assertJsonPath('data.customer.mobile', '09121112233');

        $customer->refresh();
        $this->assertSame('09121112233', $customer->mobile);
        $this->assertSame('سعید رضایی جدید', $customer->name);
    }
}
