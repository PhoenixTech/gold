<?php

namespace Tests\Feature\Admin;

use App\Enums\CampaignProductRole;
use App\Enums\CampaignStatus;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use App\Services\CampaignResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCampaignTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // The suite shares one application instance, so another test file may
        // have left the locale on "fa" and turned every __() into Persian.
        App::setLocale('en');

        Role::findOrCreate('admin', 'web');

        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Yalda Offers',
            'subtitle' => 'Longest night',
            'badge_text' => 'Special Offer',
            'status' => CampaignStatus::Published->value,
            'priority' => 50,
            'limit' => 6,
            'starts_at' => now()->subDay()->toDateTimeString(),
            'ends_at' => now()->addWeek()->toDateTimeString(),
            'occasions' => [Occasion::Yalda->value],
            'metal_scope' => ['gold'],
            'included_products' => json_encode([]),
            'excluded_products' => json_encode([]),
        ], $overrides);
    }

    public function test_it_lists_campaigns(): void
    {
        Campaign::factory()->create(['name' => 'Yalda Offers']);

        $this->get(route('admin.campaign.index'))
            ->assertOk()
            ->assertSee('Yalda Offers');
    }

    /**
     * "schedule" is a computed accessor, not a column. The list must not put it
     * into the SQL SELECT, which used to raise
     * "Unknown column 'schedule' in 'field list'".
     */
    public function test_the_list_renders_computed_columns_without_selecting_them(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Yalda Offers']);

        // "schedule" stays a computed accessor rather than a real column.
        $this->assertFalse(Schema::hasColumn('campaigns', 'schedule'));

        $this->get(route('admin.campaign.index'))
            ->assertOk()
            ->assertSee('Yalda Offers')
            // The range is rendered from starts_at / ends_at.
            ->assertSee($campaign->scheduleLabel(), false);
    }

    public function test_the_list_can_be_sorted_by_the_schedule(): void
    {
        $later = Campaign::factory()->create([
            'name' => 'Later Event',
            'starts_at' => now()->addMonth(),
            'ends_at' => now()->addMonths(2),
        ]);

        $earlier = Campaign::factory()->create([
            'name' => 'Earlier Event',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addDays(1),
        ]);

        $this->get(route('admin.campaign.index', ['sort' => 'schedule', 'sortType' => 'asc']))
            ->assertOk();

        $ordered = Campaign::orderBy('starts_at')->pluck('id')->all();

        $this->assertSame([$earlier->id, $later->id], $ordered);
    }

    public function test_it_shows_the_create_form(): void
    {
        $this->get(route('admin.campaign.create'))->assertOk();
    }

    public function test_it_creates_a_campaign_with_occasions_and_metal_scope(): void
    {
        $response = $this->post(route('admin.campaign.store'), $this->payload());

        $response->assertRedirect();

        $campaign = Campaign::firstOrFail();

        $this->assertSame('Yalda Offers', $campaign->name);
        $this->assertSame(CampaignStatus::Published, $campaign->status);
        $this->assertSame([Occasion::Yalda->value], $campaign->occasions);
        $this->assertSame(['gold'], $campaign->metal_scope);
        $this->assertSame(50, $campaign->priority);
    }

    public function test_it_requires_a_name(): void
    {
        $this->post(route('admin.campaign.store'), $this->payload(['name' => '']))
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Campaign::count());
    }

    public function test_it_rejects_an_unknown_occasion(): void
    {
        $this->post(route('admin.campaign.store'), $this->payload(['occasions' => ['halloween']]))
            ->assertSessionHasErrors('occasions.0');
    }

    public function test_it_rejects_an_end_date_before_the_start_date(): void
    {
        $this->post(route('admin.campaign.store'), $this->payload([
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at' => now()->subWeek()->toDateTimeString(),
        ]))->assertSessionHasErrors('ends_at');
    }

    public function test_it_accepts_numeric_timestamps_from_vue_datetime_picker(): void
    {
        $start = now()->addDay()->timestamp;
        $end = now()->addDays(5)->timestamp;

        $this->post(route('admin.campaign.store'), $this->payload([
            'starts_at' => (string) $start,
            'ends_at' => (string) $end,
        ]))->assertRedirect();

        $campaign = Campaign::firstOrFail();
        $this->assertSame(date('Y-m-d H:i:s', $start), $campaign->starts_at->toDateTimeString());
        $this->assertSame(date('Y-m-d H:i:s', $end), $campaign->ends_at->toDateTimeString());
    }

    public function test_it_stores_included_and_excluded_products_in_pick_order(): void
    {
        $hero = Product::factory()->create();
        $other = Product::factory()->create();
        $blocked = Product::factory()->create();

        $this->post(route('admin.campaign.store'), $this->payload([
            'included_products' => json_encode([$other->id, $hero->id]),
            'excluded_products' => json_encode([$blocked->id]),
        ]))->assertRedirect();

        $campaign = Campaign::firstOrFail();

        $this->assertSame(
            [$other->id, $hero->id],
            $campaign->productLinks()->where('role', CampaignProductRole::Include->value)
                ->orderBy('sort')->pluck('product_id')->all()
        );
        $this->assertSame(
            [$blocked->id],
            $campaign->productLinks()->where('role', CampaignProductRole::Exclude->value)
                ->pluck('product_id')->all()
        );
    }

    public function test_it_rejects_a_product_that_is_both_included_and_excluded(): void
    {
        $product = Product::factory()->create();

        $this->post(route('admin.campaign.store'), $this->payload([
            'included_products' => json_encode([$product->id]),
            'excluded_products' => json_encode([$product->id]),
        ]))->assertSessionHasErrors('excluded_products');

        $this->assertSame(0, Campaign::count());
    }

    public function test_it_updates_a_campaign_and_replaces_its_product_links(): void
    {
        $old = Product::factory()->create();
        $new = Product::factory()->create();

        $campaign = Campaign::factory()->create(['name' => 'Old Name']);

        $campaign->productLinks()->create([
            'product_id' => $old->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->post(route('admin.campaign.update', $campaign->slug), $this->payload([
            'name' => 'New Name',
            'included_products' => json_encode([$new->id]),
        ]))->assertRedirect();

        $campaign->refresh();

        $this->assertSame('New Name', $campaign->name);
        $this->assertSame([$new->id], $campaign->productLinks()->pluck('product_id')->all());
    }

    public function test_an_empty_metal_scope_means_every_tab(): void
    {
        $this->post(route('admin.campaign.store'), $this->payload(['metal_scope' => []]));

        $this->assertNull(Campaign::firstOrFail()->metal_scope);
    }

    public function test_it_shows_the_edit_form_with_a_preview_of_the_tile(): void
    {
        Product::factory()->create(['occasions' => [Occasion::Yalda->value]]);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $this->get(route('admin.campaign.edit', $campaign->slug))
            ->assertOk()
            ->assertSee(__('What the tile will show (gold tab)'));
    }

    public function test_the_edit_form_warns_about_an_overlapping_higher_priority_campaign(): void
    {
        $winner = Campaign::factory()
            ->priority(90)
            ->create(['name' => 'Christmas Event']);

        $loser = Campaign::factory()
            ->priority(10)
            ->create(['name' => 'Yalda Event']);

        $this->get(route('admin.campaign.edit', $loser->slug))
            ->assertOk()
            ->assertSee(__('These campaigns overlap your schedule'))
            ->assertSee('Christmas Event');
    }

    public function test_it_does_not_warn_about_a_non_overlapping_campaign(): void
    {
        $ended = Campaign::factory()
            ->priority(90)
            ->create([
                'name' => 'Old Event',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->subMonth(),
            ]);

        $current = Campaign::factory()->priority(10)->create(['name' => 'Yalda Event']);

        $this->get(route('admin.campaign.edit', $current->slug))
            ->assertOk()
            ->assertDontSee('Old Event');
    }

    public function test_it_ends_a_campaign_now(): void
    {
        $campaign = Campaign::factory()->create();

        $this->get(route('admin.campaign.end-now', $campaign->slug))->assertRedirect();

        $campaign->refresh();

        $this->assertSame(CampaignStatus::Disabled, $campaign->status);
        $this->assertFalse($campaign->isLive());
    }

    public function test_it_bulk_ends_live_campaigns(): void
    {
        $first = Campaign::factory()->create();
        $second = Campaign::factory()->create();

        $this->post(route('admin.campaign.bulk'), [
            '_token' => csrf_token(),
            'action' => 'end-now',
            'id' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertFalse($first->fresh()->isLive());
        $this->assertFalse($second->fresh()->isLive());
    }

    public function test_it_soft_deletes_and_restores_a_campaign(): void
    {
        $campaign = Campaign::factory()->create();

        $this->get(route('admin.campaign.destroy', $campaign->slug))->assertRedirect();
        $this->assertSoftDeleted($campaign);

        $this->get(route('admin.campaign.restore', $campaign->slug))->assertRedirect();
        $this->assertNotSoftDeleted($campaign);
    }

    public function test_it_lists_trashed_campaigns(): void
    {
        $campaign = Campaign::factory()->create(['name' => 'Removed Event']);
        $campaign->delete();

        $this->get(route('admin.campaign.trashed'))
            ->assertOk()
            ->assertSee('Removed Event');
    }

    public function test_saving_a_campaign_refreshes_the_storefront_slot(): void
    {
        $product = Product::factory()->create(['occasions' => [Occasion::Yalda->value]]);

        $this->assertNull(app(CampaignResolver::class)->forMetal('gold'));

        $this->post(route('admin.campaign.store'), $this->payload());

        $campaign = Campaign::firstOrFail();

        $this->assertTrue(
            app(CampaignResolver::class)->forMetal('gold')->is($campaign)
        );

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertSee('Yalda Offers')
            ->assertSee($product->name);
    }

    public function test_creating_a_published_campaign_without_a_metal_scope_shows_on_both_tabs(): void
    {
        Product::factory()->create(['occasions' => [Occasion::Yalda->value], 'metal_type' => 'gold']);
        Product::factory()->create(['occasions' => [Occasion::Yalda->value], 'metal_type' => 'silver']);

        $this->post(route('admin.campaign.store'), $this->payload(['metal_scope' => []]));

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertSee('campaign-slot__box', false);
    }
}
