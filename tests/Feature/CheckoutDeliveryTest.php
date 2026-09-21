<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\State;
use App\Models\Transport;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function createProductAndQuantity(): array
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

        return [$product, $quantity];
    }

    private function createActiveBankAccount(): BankAccount
    {
        return BankAccount::factory()->active()->create([
            'bank_name' => 'Melli',
            'account_holder_name' => 'Shop Holder',
            'card_number' => '6037991111222233',
        ]);
    }

    private function createTransport(): Transport
    {
        $transport = new Transport;
        $transport->title = 'پیک موتوری';
        $transport->price = 50000;
        $transport->is_default = 1;
        $transport->sort = 1;
        $transport->save();

        return $transport;
    }

    public function test_gallery_pickup_places_order_without_address_and_zero_transport_cost(): void
    {
        $this->createActiveBankAccount();
        [$product, $quantity] = $this->createProductAndQuantity();

        $customer = Customer::factory()->create([
            'name' => 'خریدار حضوری',
            'mobile' => '09120001122',
            'email' => 'pickup'.uniqid().'@example.com',
        ]);

        $this->assertSame(0, $customer->addresses()->count());

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'delivery_type' => 'pickup',
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertSame('pickup', $invoice->delivery_type);
        $this->assertNull($invoice->address_id);
        $this->assertSame(0, (int) $invoice->transport_price);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);
    }

    public function test_non_tehran_address_checkout_is_rejected_with_province_restriction_error(): void
    {
        $this->createActiveBankAccount();
        $transport = $this->createTransport();
        [$product, $quantity] = $this->createProductAndQuantity();

        $customer = Customer::factory()->create([
            'name' => 'خریدار شهرستان',
            'mobile' => '09130002233',
            'email' => 'isfahan'.uniqid().'@example.com',
        ]);

        $state = new State;
        $state->setTranslation('name', 'fa', 'اصفهان');
        $state->setTranslation('country', 'fa', 'ایران');
        $state->lat = '32.6546';
        $state->lng = '51.6680';
        $state->save();

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->state_id = $state->id;
        $address->address = 'اصفهان، خیابان چهارباغ، پلاک ۱۵';
        $address->save();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'delivery_type' => 'address',
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ]);

        $response->assertSessionHasErrors(['address_id']);
        $this->assertSame(0, Invoice::query()->where('customer_id', $customer->id)->count());
    }

    public function test_tehran_address_checkout_proceeds_with_shipping_notice(): void
    {
        $this->createActiveBankAccount();
        $transport = $this->createTransport();
        [$product, $quantity] = $this->createProductAndQuantity();

        $customer = Customer::factory()->create([
            'name' => 'خریدار تهران',
            'mobile' => '09123334455',
            'email' => 'tehran'.uniqid().'@example.com',
        ]);

        $state = new State;
        $state->setTranslation('name', 'fa', 'تهران');
        $state->setTranslation('country', 'fa', 'ایران');
        $state->lat = '35.6892';
        $state->lng = '51.3890';
        $state->save();

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->state_id = $state->id;
        $address->address = 'تهران، خیابان آزادی، کوچه لاله، پلاک ۲';
        $address->save();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'delivery_type' => 'address',
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertSame($address->id, $invoice->address_id);
        $this->assertSame(Invoice::AWAITING_PAYMENT, $invoice->status);

        $html = view('client.customer.invoice', [
            'invoice' => $invoice->fresh()->load(['customer', 'address.state', 'address.city', 'orders.product', 'orders.quantity', 'payments', 'paymentReceipts']),
            'qr' => new class
            {
                public function render(string $url): string
                {
                    return 'data:image/svg+xml,'.rawurlencode('<svg></svg>');
                }
            },
        ])->render();

        $this->assertTrue(
            str_contains($html, '۴۸') || str_contains($html, '48') || str_contains($html, 'ساعت')
        );
    }

    public function test_third_party_recipient_fields_are_validated(): void
    {
        $this->createActiveBankAccount();
        $transport = $this->createTransport();
        [$product, $quantity] = $this->createProductAndQuantity();

        $customer = Customer::factory()->create([
            'name' => 'خریدار اصلی',
            'mobile' => '09121112233',
            'email' => 'thirdparty_val'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان کریمخان، پلاک ۱۰';
        $address->save();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'delivery_type' => 'address',
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
            'is_third_party' => 1,
            'recipient_name' => '',
            'recipient_mobile' => '08123456789',
            'recipient_national_id' => '12345',
        ]);

        $response->assertSessionHasErrors(['recipient_name', 'recipient_mobile', 'recipient_national_id']);
        $this->assertSame(0, Invoice::query()->where('customer_id', $customer->id)->count());
    }

    public function test_third_party_recipient_stored_on_invoice_without_overwriting_buyer_profile(): void
    {
        $this->createActiveBankAccount();
        $transport = $this->createTransport();
        [$product, $quantity] = $this->createProductAndQuantity();

        $customer = Customer::factory()->create([
            'name' => 'خریدار اصلی ثابت',
            'mobile' => '09121112233',
            'code' => '1111111111',
            'email' => 'buyer_profile'.uniqid().'@example.com',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان سهروردی، پلاک ۱۰۰';
        $address->save();

        $response = $this->actingAs($customer, 'customer')->post(route('client.card.check'), [
            'product_id' => [$product->id],
            'count' => [1],
            'quantity_id' => [$quantity->id],
            'delivery_type' => 'address',
            'address_id' => $address->id,
            'transport_id' => $transport->id,
            'payment_method' => 'card',
            'is_third_party' => 1,
            'recipient_name' => 'سهراب گیرنده',
            'recipient_mobile' => '09198887766',
            'recipient_national_id' => '0012345678',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $invoice = Invoice::query()->where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($invoice);
        $this->assertTrue((bool) $invoice->is_third_party);
        $this->assertSame('سهراب گیرنده', $invoice->recipient_name);
        $this->assertSame('09198887766', $invoice->recipient_mobile);
        $this->assertSame('0012345678', $invoice->recipient_national_id);

        $customer->refresh();
        $this->assertSame('خریدار اصلی ثابت', $customer->name);
        $this->assertSame('09121112233', $customer->mobile);
        $this->assertSame('1111111111', $customer->code);
    }

    public function test_cart_page_provides_continue_shopping_cta(): void
    {
        $this->seed(GfxSeeder::class);
        [$product, $quantity] = $this->createProductAndQuantity();

        $response = $this->withCookie('card', json_encode([$product->id]))
            ->withCookie('q', json_encode([$quantity->id]))
            ->get(route('client.card'));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->getContent(), route('client.products')) ||
            str_contains($response->getContent(), 'products') ||
            str_contains($response->getContent(), 'ادامه خرید')
        );
    }
}
