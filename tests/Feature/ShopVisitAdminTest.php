<?php

namespace Tests\Feature;

use App\Models\ShopVisit;
use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ShopVisitAdminTest extends TestCase
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

    public function test_admin_can_list_completed_shop_visits_in_the_dashboard(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        app()->setLocale('fa');
        $this->actingAsAdmin();
        $completed = ShopVisit::factory()->create([
            'mobile' => '09123334444',
            'first_name' => 'Maryam',
            'has_purchase' => true,
            'submitted_at' => now(),
        ]);
        ShopVisit::factory()->create([
            'mobile' => '09124445555',
            'first_name' => 'NoBuy',
            'has_purchase' => false,
        ]);
        ShopVisit::factory()->collecting()->create([
            'mobile' => '09125556666',
            'first_name' => 'DraftName',
        ]);

        $response = $this->get(route('admin.shop-visit.index'));

        $response->assertOk();
        $response->assertSee('09123334444', false);
        $response->assertSee('Maryam', false);
        $response->assertDontSee('09125556666', false);
        $response->assertDontSee('DraftName', false);
        $response->assertSee(__('Shop visits'), false);
        $response->assertSee(__('has_purchase'), false);
        $response->assertSee(__('first_name'), false);
        $response->assertSee(__('last_name'), false);
        $response->assertSee(__('mall'), false);
        $response->assertSee(__('submitted_at'), false);
        $response->assertSee(__('Yes'), false);
        $response->assertSee(__('No'), false);
        $response->assertSee($completed->submittedAtLabel(), false);
        $response->assertDontSee($completed->submitted_at->format('Y-m-d H:i'), false);
        $response->assertSee(route('admin.shop-visit.export'), false);
        $this->assertNotNull($completed->id);
    }

    public function test_admin_can_export_completed_visits_as_csv(): void
    {
        app()->setLocale('fa');
        $this->actingAsAdmin();
        $visit = ShopVisit::factory()->create([
            'mobile' => '09127778888',
            'first_name' => 'ExportMe',
            'has_purchase' => true,
        ]);

        $response = $this->get(route('admin.shop-visit.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('09127778888', $content);
        $this->assertStringContainsString('ExportMe', $content);
        $this->assertStringContainsString('mobile', $content);
        $this->assertStringContainsString(__('Yes'), $content);
        $this->assertStringContainsString($visit->submittedAtLabel(), $content);
        $this->assertStringNotContainsString($visit->submitted_at->format('Y-m-d H:i:s'), $content);
    }

    public function test_admin_can_view_a_shop_visit_detail(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        app()->setLocale('fa');
        $this->actingAsAdmin();
        $visit = ShopVisit::factory()->create([
            'mobile' => '09121112222',
            'address' => 'پلاک ۲۰',
            'has_purchase' => false,
        ]);

        $response = $this->get(route('admin.shop-visit.show', $visit->id));

        $response->assertOk();
        $response->assertSee('09121112222', false);
        $response->assertSee('پلاک ۲۰', false);
        $response->assertSee(__('submitted_at'), false);
        $response->assertSee($visit->submittedAtLabel(), false);
        $response->assertSee(__('No'), false);
        $response->assertDontSee($visit->submitted_at->format('Y-m-d H:i'), false);
    }

    public function test_guest_is_redirected_from_shop_visit_list(): void
    {
        $this->get(route('admin.shop-visit.index'))->assertRedirect();
    }
}
