<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function check()
    {
        $this->seed(\Database\Seeders\GfxSeeder::class);
        if (Customer::count() === 0) {
            Customer::factory(1)->create();
        }
    }
    /**
     * A basic feature test example.
     */
    public function test_customer_profile(): void
    {

        $this->check();
        $response = $this->actingAs(Customer::inRandomOrder()->first(),'customer')->get(route('client.profile'));

        $response->assertStatus(200);
    }

    public function test_customer_profile_save_with_jalali_dob_and_profile_fields(): void
    {
        $this->check();
        $customer = Customer::factory()->create([
            'name' => 'Old Name',
            'mobile' => '09389434482',
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('client.profile.save'), [
            'first_name' => 'صادق',
            'last_name' => 'پورمحمدی',
            'email' => 'sadeghpm@gmail.com',
            'sex' => 'MALE',
            'dob_year' => 1365,
            'dob_month' => 11,
            'dob_day' => 12,
            'national_code' => '5479942591',
            'emergency_phone' => '09120000000',
            'bank_card' => '6037991122334455',
            'bank_sheba' => 'IR1200000000000000000000',
        ]);

        $response->assertRedirect();

        $customer->refresh();
        $this->assertEquals('صادق پورمحمدی', $customer->name);
        $this->assertEquals('sadeghpm@gmail.com', $customer->email);
        $this->assertEquals('MALE', $customer->sex);
        $this->assertEquals('1987-02-01', $customer->dob->format('Y-m-d'));
        $this->assertEquals('5479942591', $customer->national_code);
        $this->assertEquals('09120000000', $customer->emergency_phone);
        $this->assertEquals('6037991122334455', $customer->bank_card);
        $this->assertEquals('IR1200000000000000000000', $customer->bank_sheba);
    }
}

