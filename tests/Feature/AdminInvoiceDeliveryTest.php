<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Jobs\SendDeliveryCodeSms;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Quantity;
use App\Models\Transport;
use App\Models\User;
use App\Services\DeliveryService;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminInvoiceDeliveryTest extends TestCase
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

    private function makeCourier(): User
    {
        Role::findOrCreate('courier', 'web');
        $courier = User::factory()->courier()->create(['name' => 'پیک تست']);
        $courier->assignRole('courier');

        return $courier;
    }

    /**
     * @return array{0: Invoice, 1: Transport, 2: User, 3: Address}
     */
    private function makePaidCourierInvoice(bool $requiresCode = true): array
    {
        $courier = $this->makeCourier();
        $customer = Customer::factory()->create([
            'name' => 'مریم طلایی',
            'mobile' => '09001112233',
        ]);

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = 'تهران، زعفرانیه، پلاک ۸';
        $address->zip = '1981811111';
        $address->save();

        $transport = new Transport;
        $transport->title = 'پیک موتوری';
        $transport->price = 0;
        $transport->requires_delivery_code = $requiresCode;
        $transport->save();

        $product = Product::factory()->create([
            'name' => 'انگشتر طلا',
            'status' => 1,
        ]);
        $quantity = Quantity::factory()->create([
            'product_id' => $product->id,
            'weight' => 2.5,
            'count' => 1,
        ]);

        $invoice = new Invoice;
        $invoice->customer_id = $customer->id;
        $invoice->address_id = $address->id;
        $invoice->transport_id = $transport->id;
        $invoice->status = Invoice::PAID;
        $invoice->total_price = 5_000_000;
        $invoice->count = 1;
        $invoice->save();

        $order = new Order;
        $order->invoice_id = $invoice->id;
        $order->product_id = $product->id;
        $order->quantity_id = $quantity->id;
        $order->count = 1;
        $order->price_total = 5_000_000;
        $order->save();

        return [$invoice->fresh(), $transport, $courier, $address];
    }

    public function test_admin_dispatch_creates_delivery_and_queues_sms(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice();

        $response = $this->post(route('admin.invoice.update', $invoice), [
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $transport->id,
            'address_id' => $address->id,
            'courier_id' => $courier->id,
            'tracking_code' => '',
        ]);

        $response->assertRedirect();
        $invoice->refresh();
        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->status);

        $delivery = Delivery::query()->where('invoice_id', $invoice->id)->first();
        $this->assertNotNull($delivery);
        $this->assertSame($courier->id, $delivery->courier_id);
        $this->assertSame(DeliveryStatus::Pending, $delivery->status);
        $this->assertNotNull($delivery->code_hash);

        Queue::assertPushed(SendDeliveryCodeSms::class, fn ($job) => $job->delivery->is($delivery));

        $code = Cache::get(DeliveryService::codeCacheKey($delivery));
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $code);
        $this->assertTrue(Hash::check($code, $delivery->code_hash));
    }

    public function test_non_courier_transport_does_not_create_a_delivery(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice(false);

        $this->post(route('admin.invoice.update', $invoice), [
            'status' => Invoice::PROCESSING,
            'transport_id' => $transport->id,
            'address_id' => $address->id,
            'courier_id' => $courier->id,
            'tracking_code' => '',
        ])->assertRedirect();

        $this->assertSame(0, Delivery::query()->count());
        Queue::assertNothingPushed();
        $this->assertSame(Invoice::PROCESSING, $invoice->fresh()->status);
    }

    public function test_out_for_delivery_requires_courier_transport_and_courier(): void
    {
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice(false);

        $this->from(route('admin.invoice.edit', $invoice))
            ->post(route('admin.invoice.update', $invoice), [
                'status' => Invoice::OUT_FOR_DELIVERY,
                'transport_id' => $transport->id,
                'address_id' => $address->id,
                'courier_id' => $courier->id,
                'tracking_code' => '',
            ])
            ->assertRedirect(route('admin.invoice.edit', $invoice))
            ->assertSessionHasErrors('status');

        $invoice->refresh();
        $transport->requires_delivery_code = true;
        $transport->save();

        $this->from(route('admin.invoice.edit', $invoice))
            ->post(route('admin.invoice.update', $invoice), [
                'status' => Invoice::OUT_FOR_DELIVERY,
                'transport_id' => $transport->id,
                'address_id' => $address->id,
                'tracking_code' => '',
            ])
            ->assertSessionHasErrors('courier_id');
    }

    public function test_admin_cannot_complete_courier_invoice_without_pin(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice();

        $this->post(route('admin.invoice.update', $invoice), [
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $transport->id,
            'address_id' => $address->id,
            'courier_id' => $courier->id,
            'tracking_code' => '',
        ]);

        $this->from(route('admin.invoice.edit', $invoice))
            ->post(route('admin.invoice.update', $invoice), [
                'status' => Invoice::COMPLETED,
                'transport_id' => $transport->id,
                'address_id' => $address->id,
                'courier_id' => $courier->id,
                'tracking_code' => '',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(Invoice::OUT_FOR_DELIVERY, $invoice->fresh()->status);
    }

    public function test_admin_can_resend_delivery_code(): void
    {
        Queue::fake();
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice();

        $this->post(route('admin.invoice.update', $invoice), [
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $transport->id,
            'address_id' => $address->id,
            'courier_id' => $courier->id,
            'tracking_code' => '',
        ]);

        $delivery = Delivery::query()->first();
        $oldHash = $delivery->code_hash;

        $this->post(route('admin.invoice.resend-delivery-code', $invoice))->assertRedirect();

        $delivery->refresh();
        $this->assertNotSame($oldHash, $delivery->code_hash);
        $this->assertSame(0, $delivery->failed_attempts);
        Queue::assertPushed(SendDeliveryCodeSms::class, 2);
    }

    public function test_invoice_edit_and_show_do_not_reveal_the_pin(): void
    {
        Queue::fake();
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->actingAsAdmin();
        [$invoice, $transport, $courier, $address] = $this->makePaidCourierInvoice();

        $this->post(route('admin.invoice.update', $invoice), [
            'status' => Invoice::OUT_FOR_DELIVERY,
            'transport_id' => $transport->id,
            'address_id' => $address->id,
            'courier_id' => $courier->id,
            'tracking_code' => '',
        ]);

        $delivery = Delivery::query()->first();
        $code = Cache::get(DeliveryService::codeCacheKey($delivery));

        $edit = $this->get(route('admin.invoice.edit', $invoice));
        $edit->assertOk();
        $edit->assertSee(__('Courier'), false);
        $edit->assertSee('پیک تست', false);
        $edit->assertDontSee($delivery->getAttributes()['code_hash'], false);

        $show = $this->get(route('admin.invoice.show', $invoice->hash));
        $show->assertOk();
        $show->assertSee('پیک تست', false);
        $show->assertDontSee($delivery->getAttributes()['code_hash'], false);
        $this->assertNotSame('', (string) $code);
    }

    public function test_out_for_delivery_is_a_successful_sale_status(): void
    {
        $this->assertContains(Invoice::OUT_FOR_DELIVERY, Invoice::successfulStatuses());
        $this->assertContains(Invoice::OUT_FOR_DELIVERY, Invoice::editableStatuses());
        $this->assertContains(Invoice::OUT_FOR_DELIVERY, Invoice::adminFilterStatuses());
    }

    public function test_admin_can_toggle_requires_delivery_code_on_transport(): void
    {
        $this->actingAsAdmin();

        $this->post(route('admin.transport.store'), [
            'title' => 'پیک موتوری شمال',
            'price' => 0,
            'description' => 'Tehran only',
            'requires_delivery_code' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('transports', [
            'requires_delivery_code' => 1,
        ]);
    }
}
