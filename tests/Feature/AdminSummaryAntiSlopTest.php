<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\GfxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSummaryAntiSlopTest extends TestCase
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

    public function test_admin_summary_contains_no_ai_slop(): void
    {
        $this->withoutVite();
        $this->seed(GfxSeeder::class);
        App::setLocale('fa');
        $this->actingAsAdmin();

        $response = $this->get(route('admin.summary.index'));

        $response->assertOk();
        $response->assertDontSee('—', false);
        $response->assertDontSee('Gold shop control room');
        $response->assertDontSee('Business at a glance');
        $response->assertDontSee('Store pulse');
        $response->assertDontSeeText('WAITING_RECEIPT');
        $response->assertDontSeeText('WAITING_CONFIRMATION');
        $response->assertDontSee('A clear view of sales');
        $response->assertDontSee('summary-hero');
        $response->assertDontSee('summary-eyebrow');
        $response->assertDontSee('summary-section-kicker');

        $content = $response->getContent();
        preg_match('/<section id="shop-summary-page"[^>]*>(.*?)<\/main>/is', $content, $sectionMatch);
        $sectionContent = $sectionMatch[1] ?? $content;
        preg_match_all('/<i\s+class="ri-[^"]+"([^>]*)>/i', $sectionContent, $matches);
        $this->assertNotEmpty($matches[0]);
        foreach ($matches[1] as $attributes) {
            $this->assertStringContainsString('aria-hidden="true"', $attributes);
        }
    }
}
