<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\SmsService;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GfxSeeder::class);
    }

    public function test_send_sms_in_local_environment_uses_fake_code_and_skips_sms(): void
    {
        $smsMock = Mockery::mock(SmsService::class);
        $smsMock->shouldNotReceive('send');
        $this->app->instance(SmsService::class, $smsMock);

        $mobile = '09120000001';

        $response = $this->getJson(route('client.send-sms', ['tel' => $mobile]));

        $response->assertOk()
            ->assertJsonPath('OK', true);

        $customer = Customer::query()->where('mobile', $mobile)->first();
        $this->assertNotNull($customer);
        $this->assertEquals('111111', $customer->code);
    }

    public function test_check_auth_with_fake_code_succeeds_in_local_environment(): void
    {
        $mobile = '09120000002';
        Customer::factory()->create([
            'mobile' => $mobile,
            'code' => '111111',
        ]);

        $response = $this->getJson(route('client.check-auth', [
            'tel' => $mobile,
            'code' => '111111',
        ]));

        $response->assertOk()
            ->assertJsonPath('OK', true);

        $this->assertAuthenticated('customer');
        $this->assertNull(Customer::query()->where('mobile', $mobile)->first()->code);
    }

    public function test_check_auth_with_direct_fake_code_creates_and_logs_in_customer_in_local_environment(): void
    {
        $mobile = '09120000003';

        $response = $this->getJson(route('client.check-auth', [
            'tel' => $mobile,
            'code' => '111111',
        ]));

        $response->assertOk()
            ->assertJsonPath('OK', true);

        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('customers', [
            'mobile' => $mobile,
        ]);
    }

    public function test_check_auth_with_invalid_code_fails(): void
    {
        $mobile = '09120000004';
        Customer::factory()->create([
            'mobile' => $mobile,
            'code' => '111111',
        ]);

        $response = $this->getJson(route('client.check-auth', [
            'tel' => $mobile,
            'code' => '222222',
        ]));

        $response->assertOk()
            ->assertJsonPath('OK', false);

        $this->assertGuest('customer');
    }

    public function test_send_sms_in_production_environment_sends_sms_and_generates_code(): void
    {
        $this->app['env'] = 'production';

        $smsMock = Mockery::mock(SmsService::class);
        $smsMock->shouldReceive('send')->once()->andReturn(true);
        $this->app->instance(SmsService::class, $smsMock);

        $mobile = '09120000005';

        $response = $this->getJson(route('client.send-sms', ['tel' => $mobile]));

        $response->assertOk()
            ->assertJsonPath('OK', true);

        $customer = Customer::query()->where('mobile', $mobile)->first();
        $this->assertNotNull($customer);
        $this->assertNotEquals('111111', $customer->code);
        $this->assertEquals(6, strlen((string) $customer->code));
    }
}
