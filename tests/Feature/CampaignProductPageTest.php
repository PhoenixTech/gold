<?php

namespace Tests\Feature;

use App\Enums\CampaignProductRole;
use App\Enums\CampaignStatus;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CampaignProductSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * A campaign is served by the ordinary catalog route, scoped to the campaign's
 * product set. There is no bespoke campaign view: pagination, sorting, the
 * sidebar and the empty state all come from `client.products.index`.
 */
class CampaignProductPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        clearSettingsCache();
        App::setLocale('en');
    }

    protected function tearDown(): void
    {
        clearSettingsCache();

        parent::tearDown();
    }

    private function tag(string $name, array $occasions, string $metal = 'gold'): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'metal_type' => $metal,
            'occasions' => $occasions,
        ]);
    }

    private function liveCampaign(array $attributes = []): Campaign
    {
        return Campaign::factory()->occasions([Occasion::Yalda->value])->create(array_merge([
            'status' => CampaignStatus::Published,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
        ], $attributes));
    }

    /*
    |--------------------------------------------------------------------------
    | Reaching the page
    |--------------------------------------------------------------------------
    */

    public function test_the_campaign_page_is_the_catalog_scoped_to_the_campaign(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);
        $this->tag('OtherRing', [Occasion::Birthday->value]);

        $campaign = $this->liveCampaign(['name' => 'Yalda Offers']);

        $this->get(route('client.products', ['campaign' => $campaign->slug]))
            ->assertOk()
            ->assertSee('Yalda Offers')
            ->assertSee('YaldaRing')
            ->assertDontSee('OtherRing');
    }

    public function test_it_renders_the_campaign_header(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        $campaign = $this->liveCampaign([
            'subtitle' => 'Longest night of the year',
            'badge_text' => 'Special Offer',
            'description' => 'A hand picked Yalda collection.',
        ]);

        $this->get($campaign->url())
            ->assertOk()
            ->assertSee('Longest night of the year')
            ->assertSee('Special Offer')
            ->assertSee('A hand picked Yalda collection.');
    }

    public function test_a_draft_campaign_is_not_reachable_for_a_shopper(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        $campaign = $this->liveCampaign(['status' => CampaignStatus::Draft]);

        $this->get(route('client.products', ['campaign' => $campaign->slug]))->assertNotFound();
    }

    public function test_an_expired_campaign_is_not_reachable(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        $campaign = $this->liveCampaign(['starts_at' => now()->subMonth(), 'ends_at' => now()->subDay()]);

        $this->get(route('client.products', ['campaign' => $campaign->slug]))->assertNotFound();
    }

    public function test_a_signed_in_panel_user_can_preview_a_draft(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        $campaign = $this->liveCampaign(['status' => CampaignStatus::Draft]);

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('client.products', ['campaign' => $campaign->slug]))
            ->assertOk()
            ->assertSee('YaldaRing');
    }

    public function test_an_unknown_campaign_slug_is_a_404(): void
    {
        $this->get(route('client.products', ['campaign' => 'no-such-campaign']))->assertNotFound();
    }

    /*
    |--------------------------------------------------------------------------
    | The catalog still behaves like a catalog
    |--------------------------------------------------------------------------
    */

    public function test_the_campaign_page_paginates(): void
    {
        foreach (range(1, 15) as $i) {
            $this->tag("YaldaRing{$i}", [Occasion::Yalda->value]);
        }

        $campaign = $this->liveCampaign();

        $response = $this->get($campaign->url());

        $response->assertOk();
        $this->assertSame(15, $response->viewData('products')->total());
        $this->assertCount(12, $response->viewData('products')->items());
    }

    public function test_the_campaign_page_respects_the_metal_filter(): void
    {
        $this->tag('GoldRing', [Occasion::Yalda->value], 'gold');
        $this->tag('SilverRing', [Occasion::Yalda->value], 'silver');

        $campaign = $this->liveCampaign();

        $this->get($campaign->url('gold'))
            ->assertOk()
            ->assertSee('GoldRing')
            ->assertDontSee('SilverRing');

        $this->get($campaign->url('silver'))
            ->assertOk()
            ->assertSee('SilverRing')
            ->assertDontSee('GoldRing');
    }

    public function test_the_campaign_page_keeps_hand_picked_products_first(): void
    {
        $auto = $this->tag('AutoRing', [Occasion::Yalda->value]);
        $hero = Product::factory()->create(['name' => 'HeroRing', 'occasions' => []]);

        $campaign = $this->liveCampaign();
        $campaign->productLinks()->create([
            'product_id' => $hero->id,
            'role' => CampaignProductRole::Include,
        ]);

        $page = $this->get($campaign->url())->assertOk()->viewData('products');

        $this->assertSame([$hero->id, $auto->id], $page->pluck('id')->all());
    }

    public function test_an_explicit_sort_overrides_the_campaign_order(): void
    {
        $auto = Product::factory()->create([
            'name' => 'AutoRing',
            'occasions' => [Occasion::Yalda->value],
            'price' => 100,
        ]);

        $hero = Product::factory()->create([
            'name' => 'HeroRing',
            'occasions' => [],
            'price' => 500,
        ]);

        $campaign = $this->liveCampaign();
        $campaign->productLinks()->create([
            'product_id' => $hero->id,
            'role' => CampaignProductRole::Include,
        ]);

        // Campaign order puts the pinned hero first...
        $this->assertSame(
            [$hero->id, $auto->id],
            $this->get($campaign->url())->assertOk()->viewData('products')->pluck('id')->all()
        );

        // ...but an explicit sort wins.
        $this->assertSame(
            [$auto->id, $hero->id],
            $this->get($campaign->url().'&sort=cheap')->assertOk()->viewData('products')->pluck('id')->all()
        );
    }

    public function test_the_campaign_page_shows_the_empty_state_when_nothing_matches(): void
    {
        $campaign = $this->liveCampaign();

        $this->get($campaign->url())
            ->assertOk()
            ->assertSee(__('No products found matching your filters.'));
    }

    public function test_the_plain_catalog_page_is_unaffected(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        $this->get(route('client.products'))
            ->assertOk()
            ->assertSee('YaldaRing')
            ->assertDontSee('campaign-header');
    }

    public function test_the_campaign_page_shows_the_products_the_tile_advertises(): void
    {
        foreach (range(1, 3) as $i) {
            $this->tag("YaldaRing{$i}", [Occasion::Yalda->value]);
        }

        $campaign = $this->liveCampaign(['limit' => 1]);

        // The tile caps at the campaign limit, the page lists the whole set.
        $this->assertCount(1, app(CampaignProductSource::class)->tile($campaign, 'gold'));

        $page = $this->get($campaign->url())->assertOk()->viewData('products');

        $this->assertSame(3, $page->total());
    }

    public function test_the_category_redirect_still_wins_over_the_campaign_parameter(): void
    {
        Category::updateOrCreate(['slug' => 'rings'], ['name' => 'Rings', 'hide' => 0]);

        $this->get(route('client.products', ['campaign' => 'whatever', 'category' => 'rings']))
            ->assertRedirect(route('client.category', ['category' => 'rings', 'campaign' => 'whatever']));
    }
}
