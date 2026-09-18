<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(GfxSeeder::class);
    }

    public function test_customer_account_page_contains_all_reference_sections(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'صادق پورمحمدی',
            'mobile' => '09389434482',
            'credit' => 1500000,
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();

        // 1. Top Header Card
        $response->assertSee('صادق پورمحمدی');
        $response->assertSee('09389434482');
        $response->assertSee(__('Edit info'));

        // 2. Wallet Balance Card
        $response->assertSee(__('Account balance'));
        $response->assertSee('1,500,000');
        $response->assertSee(__('My balance and credit'));

        // Membership and Purchase Credit cards removed per user request
        $response->assertDontSee(__('Membership: Silver'));
        $response->assertDontSee(__('Purchase credit / Discounts / Gifts'));

        // Header and Footer should be removed from customer profile
        $response->assertDontSee('WTFFooter');
        $response->assertDontSee('id="zar-menu"', false);

        // Header should still be present on other client pages
        $welcomeResponse = $this->get(route('client.welcome'));
        $welcomeResponse->assertSee('id="zar-menu"', false);

        // Bottom Navigation Bar
        $response->assertSee(__('Home'));
        $response->assertSee(__('Products'));
        $response->assertSee(__('Orders'));
        $response->assertSee(__('Account'));

        // Streamlined Vertical Menu List items & Sub-views
        $response->assertSee(__('Active orders'));
        $response->assertSee(__('Previous orders & invoices'));
        $response->assertSee(__('Favorites'));
        $response->assertSee(__('Addresses'));
        $response->assertSee(__('Personal info / account details'));
        $response->assertSee(__('Support'));
        $response->assertSee(__('Sign-out'));
    }

    public function test_account_details_and_edit_forms_match_spec(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'صادق پورمحمدی',
            'mobile' => '09389434482',
            'email' => 'sadeghpm@gmail.com',
            'dob' => '1987-02-01',
            'sex' => 'MALE',
            'national_code' => '5479942591',
            'emergency_phone' => '09120000000',
            'bank_card' => '6037991122334455',
            'bank_sheba' => 'IR1200000000000000000000',
        ]);

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();

        // Details sub-view (wish-profile.png)
        $response->assertSee(__('Account details'));
        $response->assertSee($customer->dob->jdate('j F Y'));
        $response->assertSee(__('Verified'));
        $response->assertSee('sadeghpm@gmail.com');
        $response->assertSee(__('Change password'));
        $response->assertSee(__('Bank account info'));

        // Edit sub-view (wish-profile-edit.png)
        $response->assertSee(__('First name'));
        $response->assertSee(__('Last name'));
        $response->assertSee(__('Date of birth'));
        $response->assertSee('بهمن');
        $response->assertSee(__('National code'));
        $response->assertSee(__('Emergency contact number'));
        $response->assertSee(__('Emergency contact note'));
    }

    public function test_support_faq_step_and_ticket_action(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();
        $response->assertSee(__('Frequently Asked Questions'));
        $response->assertSee(__("Order hasn't arrived yet, what should I do?"));
        $response->assertSee(__('Courier arrived, what should I do?'));
        $response->assertSee(__('Who do I contact for order follow-up or changes?'));
        $response->assertSee(__('How do I pay offline and upload a receipt?'));
        $response->assertSee(__('Need more help?'));
        $response->assertSee(__('Submit new ticket'));
    }

    public function test_empty_state_for_favorites_matches_wish_submenu(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();
        $response->assertSee(__('Your favorites list is empty'));
    }

    public function test_favorites_list_displays_product_when_present(): void
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $customer->favorites()->attach($product->id);

        $response = $this->actingAs($customer, 'customer')->get(route('client.profile'));

        $response->assertOk();
        $response->assertDontSee(__('Your favorites list is empty'));
        $response->assertSee($product->name);
    }

    public function test_all_translation_keys_in_profile_view_exist_in_fa_json(): void
    {
        $blade = file_get_contents(resource_path('views/client/customer/profile.blade.php'));
        $fa = json_decode(file_get_contents(resource_path('lang/fa.json')), true);

        preg_match_all("/__\(\s*[\x27\x22](.*?)[\x27\x22]\s*[\),]/", $blade, $matches);
        $keys = array_unique($matches[1]);

        $missing = [];
        foreach ($keys as $key) {
            if (! array_key_exists($key, $fa) || trim((string) $fa[$key]) === '') {
                $missing[] = $key;
            }
        }

        $this->assertEmpty($missing, 'The following translation keys in customer profile are missing or empty in resources/lang/fa.json: '.implode(', ', $missing));
    }
}
