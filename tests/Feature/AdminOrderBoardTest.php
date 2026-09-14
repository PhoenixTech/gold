<?php

namespace Tests\Feature;

use App\Enums\DeliveryStatus;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\State;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
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
        $response->assertSee('#' . $activeOrder->hash);
        $response->assertDontSee('#' . $completedOrder->hash);

        // Completed scope: completed orders
        $completedResponse = $this->get(route('admin.order-board.index', ['scope' => 'completed']));
        $completedResponse->assertOk();
        $completedResponse->assertSee('#' . $completedOrder->hash);
        $completedResponse->assertDontSee('#' . $activeOrder->hash);
    }

    public function test_order_board_displays_customer_code_province_and_expandable_address(): void
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
        $response->assertSee('ZK-777');
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
}
