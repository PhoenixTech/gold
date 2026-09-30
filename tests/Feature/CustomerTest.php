<?php

namespace Tests\Feature;

use App\Models\Customer;
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
}
