<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\City;
use App\Models\Customer;
use App\Models\State;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function check()
    {
        $this->seed(GfxSeeder::class);
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
        $response = $this->actingAs(Customer::inRandomOrder()->first(), 'customer')->get(route('client.profile'));

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

    public function test_profile_does_not_require_email_for_profile_completion(): void
    {
        $this->check();
        $customer = Customer::factory()->create([
            'name' => 'تست مشتری',
            'email' => null,
        ]);

        $address = new \App\Models\Address();
        $address->customer_id = $customer->id;
        $address->address = 'تهران خیابان تست';
        $address->save();

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();
        $response->assertDontSee(__('Your profile is incomplete. Required fields:'), false);
        $response->assertDontSee('data-profile-incomplete="true"', false);
    }

    public function test_profile_does_not_show_payment_receipt_required_alert(): void
    {
        $this->check();
        $customer = Customer::factory()->create([
            'name' => 'تست مشتری',
            'email' => null,
        ]);

        $invoice = new \App\Models\Invoice();
        $invoice->customer_id = $customer->id;
        $invoice->status = \App\Models\Invoice::AWAITING_PAYMENT;
        $invoice->total_price = 100000;
        $invoice->hash = 'test-hash-'.uniqid();
        $invoice->created_at = now();
        $invoice->save();

        $payment = new \App\Models\Payment();
        $payment->invoice_id = $invoice->id;
        $payment->type = 'CARD';
        $payment->status = \App\Models\Payment::PENDING;
        $payment->amount = 100000;
        $payment->order_id = 'ORD-'.uniqid();
        $payment->save();

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();
        $response->assertDontSee(__('Payment receipt required'), false);
        $response->assertDontSee('بارگذاری رسید پرداخت الزامی است.', false);
    }

    public function test_customer_can_destroy_own_address(): void
    {
        $this->check();
        $customer = Customer::factory()->create();
        $address = new Address();
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان ولیعصر، پلاک ۱۰';
        $address->save();

        $response = $this->actingAs($customer, 'customer')
            ->get(route('client.address.destroy', $address->id));

        $response->assertOk();
        $response->assertJson(['OK' => true]);
        $this->assertDatabaseMissing('addresses', ['id' => $address->id]);
    }

    public function test_customer_cannot_destroy_other_customer_address(): void
    {
        $this->check();
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();

        $address = new Address();
        $address->customer_id = $customer2->id;
        $address->address = 'تهران، خیابان شریعتی، پلاک ۲۰';
        $address->save();

        $response = $this->actingAs($customer1, 'customer')
            ->get(route('client.address.destroy', $address->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('addresses', ['id' => $address->id]);
    }

    public function test_customer_can_update_own_address(): void
    {
        $this->check();
        $this->seed(\Database\Seeders\StateSeeder::class);
        $customer = Customer::factory()->create();
        $state = State::first();
        $city = City::where('state_id', $state->id)->first();

        $address = new Address();
        $address->customer_id = $customer->id;
        $address->address = 'تهران، خیابان قدیمی، کوچه ۱';
        $address->state_id = $state->id;
        $address->city_id = $city->id;
        $address->zip = '1234567890';
        $address->save();

        $response = $this->actingAs($customer, 'customer')
            ->post(route('client.address.update', $address->id), [
                'address' => 'تهران، خیابان جدید بروز شده، پلاک ۱۲',
                'state_id' => $state->id,
                'city_id' => $city->id,
                'zip' => '9876543210',
            ]);

        $response->assertOk();
        $response->assertJson(['OK' => true]);
        $this->assertDatabaseHas('addresses', [
            'id' => $address->id,
            'address' => 'تهران، خیابان جدید بروز شده، پلاک ۱۲',
            'zip' => '9876543210',
        ]);
    }

    public function test_profile_renders_saved_birthday_and_omits_password_and_static_message(): void
    {
        $this->check();
        $customer = Customer::factory()->create([
            'dob' => '1987-02-01',
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->withSession(['message' => 'پروفایل بروزرسانی شد'])
            ->get(route('client.profile'));

        $response->assertOk();
        $response->assertSee('value="1365" selected', false);
        $response->assertSee('value="11" selected', false);
        $response->assertSee('value="12" selected', false);
        $response->assertDontSee('name="password_confirmation"', false);
        $response->assertDontSee('alert-dismissible', false);
    }
}
