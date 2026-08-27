<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use Database\Factories\DeliveryFactory;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourierDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function makeCourier(array $attributes = []): User
    {
        Role::findOrCreate('courier', 'web');
        $courier = User::factory()->courier()->create($attributes);
        $courier->assignRole('courier');

        return $courier;
    }

    private function actingAsCourier(?User $courier = null): User
    {
        $courier ??= $this->makeCourier(['name' => 'پیک اول']);
        $this->actingAs($courier);

        return $courier;
    }

    private function actingAsAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');
        $this->actingAs($user);

        return $user;
    }

    /**
     * @return array{0: Delivery, 1: Invoice, 2: User}
     */
    private function makeAssignedDelivery(?User $courier = null, string $status = 'pending'): array
    {
        $courier ??= $this->makeCourier();
        $customer = Customer::factory()->create([
            'name' => 'سارا خریدار',
            'mobile' => '09002223344',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، پاسداران، پلاک ۲۰';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک';
        $transport->price = 0;
        $transport->requires_delivery_code = true;
        $transport->save();

        $product = Product::factory()->create(['name' => 'گردنبند طلا', 'status' => 1]);
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 3.2,
            'count' => 1,
        ]);

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->status = Invoice::OUT_FOR_DELIVERY;
        $invoice->total_price = 8_000_000;
        $invoice->count = 1;
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = 8_000_000;
        $order->save();

        $delivery = Delivery::factory()->create([
            'invoice_id' => $invoice->id,
            'courier_id' => $courier->id,
            'status' => $status === 'accepted' ? DeliveryStatus::Accepted : DeliveryStatus::Pending,
            'accepted_at' => $status === 'accepted' ? now() : null,
            'code_hash' => Hash::make(DeliveryFactory::TEST_PIN),
        ]);

        return [$delivery, $invoice->fresh(), $courier];
    }

    public function test_courier_login_goes_to_the_delivery_board(): void
    {
        $this->makeCourier([
            'email' => 'courier@example.com',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'courier@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.delivery.index'));
    }

    public function test_courier_dashboard_lists_assigned_jobs_without_the_pin(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery();
        $this->actingAs($courier);

        $response = $this->get(route('admin.delivery.index'));

        $response->assertOk();
        $response->assertSee('سارا خریدار', false);
        $response->assertSee('تهران، پاسداران، پلاک ۲۰', false);
        $response->assertSee('گردنبند طلا', false);
        $response->assertSee($invoice->hash, false);
        $response->assertSee(__('Ask the customer for the SMS code before handing over the gold. The code is not shown here.'), false);
        $response->assertDontSee($delivery->getAttributes()['code_hash'], false);
        $response->assertDontSee(DeliveryFactory::TEST_PIN, false);
    }

    public function test_courier_can_accept_and_reject_assigned_delivery(): void
    {
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery();
        $this->actingAs($courier);

        $this->from(route('admin.delivery.index'))
            ->post(route('admin.delivery.accept', $delivery))
            ->assertRedirect();

        $this->assertSame(DeliveryStatus::Accepted, $delivery->fresh()->status);

        $this->from(route('admin.delivery.index'))
            ->post(route('admin.delivery.reject', $delivery), [
                'reason' => 'آدرس خارج از محدوده است',
            ])
            ->assertRedirect();

        $this->assertSame(DeliveryStatus::Rejected, $delivery->fresh()->status);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);
    }

    public function test_correct_pin_completes_delivery_and_invoice(): void
    {
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery(status: 'accepted');
        $this->actingAs($courier);

        $this->from(route('admin.delivery.index'))
            ->post(route('admin.delivery.confirm', $delivery), [
                'code' => DeliveryFactory::TEST_PIN,
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertSame(DeliveryStatus::Delivered, $delivery->fresh()->status);
        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
    }

    public function test_persian_digits_are_accepted_as_the_pin(): void
    {
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery(status: 'accepted');
        $this->actingAs($courier);

        $this->post(route('admin.delivery.confirm', $delivery), [
            'code' => '۴۲۴۲',
        ])->assertRedirect();

        $this->assertSame(Invoice::COMPLETED, $invoice->fresh()->status);
    }

    public function test_wrong_pin_increments_attempts_then_locks(): void
    {
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery(status: 'accepted');
        $this->actingAs($courier);

        for ($i = 1; $i <= 4; $i++) {
            $this->from(route('admin.delivery.index'))
                ->post(route('admin.delivery.confirm', $delivery), ['code' => '0000'])
                ->assertSessionHasErrors('code');
        }

        $this->assertSame(4, $delivery->fresh()->failed_attempts);
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);

        $this->from(route('admin.delivery.index'))
            ->post(route('admin.delivery.confirm', $delivery), ['code' => '0000'])
            ->assertSessionHasErrors('code');

        $this->assertTrue($delivery->fresh()->isLocked());
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);

        $this->from(route('admin.delivery.index'))
            ->post(route('admin.delivery.confirm', $delivery), ['code' => DeliveryFactory::TEST_PIN])
            ->assertSessionHasErrors('code');

        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_another_courier_cannot_act_on_the_job(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        [$delivery] = $this->makeAssignedDelivery();
        $other = $this->makeCourier(['name' => 'پیک دیگر']);
        $this->actingAs($other);

        $this->get(route('admin.delivery.index'))->assertOk()->assertDontSee('سارا خریدار', false);

        $this->post(route('admin.delivery.accept', $delivery))->assertForbidden();
        $this->post(route('admin.delivery.confirm', $delivery), [
            'code' => DeliveryFactory::TEST_PIN,
        ])->assertForbidden();
    }

    public function test_courier_cannot_open_admin_pages(): void
    {
        $this->actingAsCourier();

        $this->get(route('admin.product.index'))->assertRedirect(route('admin.delivery.index'));
        $this->get(route('admin.invoice.index'))->assertRedirect(route('admin.delivery.index'));
        $this->get(route('admin.home'))->assertRedirect(route('admin.delivery.index'));
    }

    public function test_admin_cannot_use_the_courier_board(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.delivery.index'))->assertForbidden();
    }

    public function test_courier_can_mark_an_accepted_delivery_as_failed(): void
    {
        [$delivery, $invoice, $courier] = $this->makeAssignedDelivery(status: 'accepted');
        $this->actingAs($courier);

        $this->post(route('admin.delivery.fail', $delivery), [
            'reason' => 'مشتری نبود',
        ])->assertRedirect();

        $this->assertSame(DeliveryStatus::Failed, $delivery->fresh()->status);
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);
    }

    public function test_dashboard_sidebar_for_courier_only_shows_deliveries(): void
    {
        $this->actingAsCourier();

        $html = view('components.panel-side-navbar')->render();

        $this->assertStringContainsString(route('admin.delivery.index'), $html);
        $this->assertStringContainsString(__('Deliveries'), $html);
        $this->assertStringNotContainsString(route('admin.product.index'), $html);
        $this->assertStringNotContainsString(route('admin.invoice.index'), $html);
        $this->assertStringNotContainsString(route('admin.help'), $html);
    }
}
