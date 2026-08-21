<?php

namespace Tests\Feature;

use App\Enums\ShopVisitStatus;
use App\Models\City;
use App\Models\ShopVisit;
use App\Models\State;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VisitorFormTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVisitor(): User
    {
        Role::findOrCreate('visitor', 'web');
        $user = User::factory()->visitor()->create();
        $user->assignRole('visitor');
        $this->actingAs($user);

        return $user;
    }

    private function makeTehranCity(): City
    {
        $state = new State;
        $state->name = 'تهران';
        $state->lat = 35.6892;
        $state->lng = 51.3890;
        $state->country = 'Iran';
        $state->save();

        $otherState = new State;
        $otherState->name = 'اصفهان';
        $otherState->lat = 32.65;
        $otherState->lng = 51.67;
        $otherState->country = 'Iran';
        $otherState->save();

        $city = new City;
        $city->name = 'تهران';
        $city->state_id = $state->id;
        $city->save();

        $otherCity = new City;
        $otherCity->name = 'اصفهان';
        $otherCity->state_id = $otherState->id;
        $otherCity->save();

        return $city;
    }

    public function test_visitor_login_redirects_to_dashboard_form(): void
    {
        Role::findOrCreate('visitor', 'web');
        $user = User::factory()->visitor()->create([
            'email' => 'visitor@example.com',
        ]);
        $user->assignRole('visitor');

        $response = $this->post(route('login'), [
            'email' => 'visitor@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.home'));
    }

    public function test_dashboard_shows_the_blank_two_step_form_for_visitors(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $city = $this->makeTehranCity();
        $this->actingAsVisitor();

        $response = $this->get(route('admin.home'));

        $response->assertOk();
        $response->assertSee('visit-sheet', false);
        $response->assertSee(__('Register shop'), false);
        $response->assertSee(__('Part 1'), false);
        $response->assertSee(__('Continue'), false);
        $response->assertSee(__('Mobile number'), false);
        $response->assertSee('type="radio"', false);
        $response->assertSee('type="checkbox"', false);
        $response->assertDontSee('visually-hidden', false);
        $response->assertSee('btn btn-primary', false);
        $response->assertDontSee('shop-dashboard', false);
        $response->assertDontSee('id="panel-breadcrumb"', false);
        $response->assertDontSee('کارگاه تولیدی طلا', false);
        $response->assertSee('value="'.$city->state_id.'"', false);
        $response->assertSee('selected', false);
        $this->assertSame(1, ShopVisit::query()->count());
        $visit = ShopVisit::query()->first();
        $this->assertSame($city->state_id, $visit->state_id);
        $this->assertSame($city->id, $visit->city_id);
    }

    public function test_visitor_cannot_open_two_forms_at_once(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->makeTehranCity();
        $this->actingAsVisitor();

        $this->get(route('admin.home'))->assertOk();
        $this->get(route('admin.home'))->assertOk();

        $this->assertSame(1, ShopVisit::query()->open()->count());
        $this->assertSame(1, ShopVisit::query()->count());
    }

    public function test_visitor_is_kept_on_dashboard_and_cannot_see_saved_visits(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->makeTehranCity();
        $visitor = $this->actingAsVisitor();
        ShopVisit::factory()->create([
            'user_id' => $visitor->id,
            'mobile' => '09120000000',
            'first_name' => 'Hidden',
        ]);

        $this->get(route('admin.shop-visit.index'))->assertRedirect(route('admin.home'));
        $this->get(route('admin.product.index'))->assertRedirect(route('admin.home'));

        $dashboard = $this->get(route('admin.home'));
        $dashboard->assertOk();
        $dashboard->assertDontSee('09120000000', false);
        $dashboard->assertDontSee('Hidden', false);
    }

    public function test_step_one_requires_a_reason_when_they_do_not_buy(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $this->makeTehranCity();
        $this->actingAsVisitor();
        $this->get(route('admin.home'));

        $response = $this->from(route('admin.home'))->post(route('admin.shop-visit.step-one'), [
            'mobile' => '09121234567',
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'has_purchase' => '0',
        ]);

        $response->assertRedirect(route('admin.home'));
        $response->assertSessionHasErrors('other_reason');
        $this->assertSame(ShopVisitStatus::Collecting, ShopVisit::query()->first()->status);
    }

    public function test_visitor_completes_both_steps_then_gets_a_new_blank_form(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        $city = $this->makeTehranCity();
        $visitor = $this->actingAsVisitor();
        $this->get(route('admin.home'));

        $this->post(route('admin.shop-visit.step-one'), [
            'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
            'first_name' => 'علی',
            'last_name' => 'رضایی',
            'has_purchase' => '0',
            'has_own_workshop' => '1',
        ])->assertRedirect(route('admin.home'));

        $open = ShopVisit::query()->open()->first();
        $this->assertSame(ShopVisitStatus::StepTwo, $open->status);
        $this->assertSame('09121234567', $open->mobile);

        $stepTwo = $this->get(route('admin.home'));
        $stepTwo->assertOk();
        $stepTwo->assertSee(__('Part 2'), false);
        $stepTwo->assertSee(__('Register shop'), false);
        $stepTwo->assertSee('علی', false);
        $stepTwo->assertSee('09121234567', false);
        $stepTwo->assertSee('value="'.$city->id.'" selected', false);
        $stepTwo->assertSee('id="visit-step-two"', false);
        $stepTwo->assertDontSee('id="visit-step-one"', false);

        $this->post(route('admin.shop-visit.step-two'), [
            'categories' => ['gold', 'licensed'],
            'work_styles' => ['minimal'],
            'state_id' => $city->state_id,
            'city_id' => $city->id,
            'mall' => 'بازار بزرگ تهران',
            'address' => 'پاساژ طلا، پلاک ۱۲',
        ])->assertRedirect(route('admin.home'));

        $this->assertSame(1, ShopVisit::query()->completed()->count());
        $completed = ShopVisit::query()->completed()->first();
        $this->assertSame($visitor->id, $completed->user_id);
        $this->assertSame($city->id, $completed->city_id);
        $this->assertContains('gold', $completed->categories);

        $newForm = $this->get(route('admin.home'));
        $newForm->assertOk();
        $newForm->assertSee(__('Part 1'), false);
        $newForm->assertDontSee('09121234567', false);
        $this->assertSame(2, ShopVisit::query()->count());
        $this->assertSame(1, ShopVisit::query()->open()->count());
    }

    public function test_regular_staff_cannot_submit_the_visitor_form(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $this->post(route('admin.shop-visit.step-one'), [
            'mobile' => '09121234567',
            'first_name' => 'Ali',
            'last_name' => 'Test',
            'has_purchase' => '1',
        ])->assertForbidden();
    }
}
