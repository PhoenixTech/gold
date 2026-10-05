<?php

namespace Tests\Feature;

use App\Enums\CampaignProductRole;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\FeaturedProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;
use Tests\TestCase;

/**
 * The 12th cell of the home page category grid.
 *
 * The grid has 11 category tiles and one campaign tile, so a live campaign with
 * products fills the empty slot and a missing or empty campaign leaves the grid
 * at 11.
 */
class HomeCampaignSlotTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        clearSettingsCache();

        // The featured products section falls back to "newest products" when no
        // tag is selected, which would leak every product name into the page and
        // make the assertions below meaningless. Point it at a tag nothing
        // carries so the section renders empty.
        $unused = Tag::findOrCreate('UnusedCampaignTag', FeaturedProductsService::TAG_TYPE);

        Setting::updateOrCreate(
            ['key' => FeaturedProductsService::SETTING_KEY],
            [
                'section' => 'Homepage',
                'type' => 'TAG_SET',
                'title' => 'Home page featured product tags',
                'value' => json_encode([$unused->id]),
                'raw' => json_encode([$unused->id]),
                'size' => 12,
            ]
        );
    }

    protected function tearDown(): void
    {
        clearSettingsCache();

        parent::tearDown();
    }

    private function tag(string $name, string $occasion, string $metal = 'gold'): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'metal_type' => $metal,
            'occasions' => [$occasion],
        ]);
    }

    /**
     * Isolate the campaign tiles from the rest of the grid, so a product name
     * can only be found inside the tile when it really is in the tile.
     *
     * @return list<string>
     */
    private function campaignSlots(string $html): array
    {
        $chunks = preg_split('/<div class="col-3 text-center mb-3[^"]*">/', $html);

        return array_values(array_filter(
            $chunks,
            static fn (string $chunk) => str_contains($chunk, 'campaign-slot__box')
        ));
    }

    public function test_the_grid_renders_eleven_cells_when_no_campaign_is_live(): void
    {
        $response = $this->get(route('client.welcome'));

        $response->assertOk();

        $this->assertSame([], $this->campaignSlots($response->getContent()));
    }

    public function test_a_live_campaign_fills_the_twelfth_cell(): void
    {
        $product = $this->tag('YaldaRing', Occasion::Yalda->value);

        Campaign::factory()
            ->occasions([Occasion::Yalda->value])
            ->create(['name' => 'Yalda Offers', 'badge_text' => 'Special Offer']);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('Yalda Offers');
        $response->assertSee('Special Offer');
        $response->assertSee('campaign-slot__box');
    }

    public function test_the_tile_shows_the_product_thumbnails_of_the_campaign(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);
        $this->tag('UntaggedRing', Occasion::Birthday->value);

        Campaign::factory()->occasions([Occasion::Yalda->value])->create(['name' => 'Yalda Offers']);

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertNotEmpty($slots);
        $this->assertStringContainsString('YaldaRing', $slots[0]);
        $this->assertStringNotContainsString('UntaggedRing', $slots[0]);
    }

    public function test_a_campaign_whose_products_are_all_gone_hides_the_cell(): void
    {
        $product = $this->tag('YaldaRing', Occasion::Yalda->value);

        Campaign::factory()->occasions([Occasion::Yalda->value])->create(['name' => 'Yalda Offers']);

        $product->update(['status' => 0]);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertDontSee('Yalda Offers');
        $this->assertSame([], $this->campaignSlots($response->getContent()));
    }

    public function test_each_metal_tab_shows_only_the_products_of_its_own_metal(): void
    {
        $this->tag('GoldRing', Occasion::Birthday->value, 'gold');
        $this->tag('SilverRing', Occasion::Birthday->value, 'silver');

        Campaign::factory()->occasions([Occasion::Birthday->value])->create(['name' => 'BirthdayEvent']);

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        // One tile per tab, both rendered into the HTML.
        $this->assertCount(2, $slots);

        $this->assertStringContainsString('GoldRing', $slots[0]);
        $this->assertStringNotContainsString('SilverRing', $slots[0]);

        $this->assertStringContainsString('SilverRing', $slots[1]);
        $this->assertStringNotContainsString('GoldRing', $slots[1]);
    }

    public function test_an_expired_campaign_is_released_without_any_cron_job(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        Campaign::factory()
            ->occasions([Occasion::Yalda->value])
            ->ended()
            ->create(['name' => 'Yalda Offers']);

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertDontSee('campaign-slot__box')
            ->assertDontSee('Yalda Offers');
    }

    public function test_a_draft_campaign_is_never_shown_even_inside_its_window(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        Campaign::factory()
            ->draft()
            ->occasions([Occasion::Yalda->value])
            ->create(['name' => 'Yalda Offers']);

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertDontSee('campaign-slot__box');
    }

    public function test_the_higher_priority_campaign_wins_the_slot(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);
        $this->tag('BirthdayRing', Occasion::Birthday->value);

        Campaign::factory()->priority(5)->occasions([Occasion::Birthday->value])->create(['name' => 'Birthday Event']);
        Campaign::factory()->priority(90)->occasions([Occasion::Yalda->value])->create(['name' => 'Yalda Event']);

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertSee('Yalda Event')
            ->assertDontSee('Birthday Event');
    }

    public function test_a_gold_only_campaign_leaves_the_silver_tab_without_a_tile(): void
    {
        $this->tag('GoldRing', Occasion::Birthday->value, 'gold');
        $this->tag('SilverRing', Occasion::Birthday->value, 'silver');

        Campaign::factory()->goldOnly()->occasions([Occasion::Birthday->value])->create();

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertCount(1, $slots);
        $this->assertStringContainsString('GoldRing', $slots[0]);
    }

    public function test_the_cell_links_to_the_campaign_product_page(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        // The metal rides along so the silver tab lands on silver products.
        // `e()` because Blade escapes the query string separator to &amp;.
        $this->assertStringContainsString(e($campaign->url('gold')), $slots[0]);
    }

    public function test_the_cell_of_the_silver_tab_links_to_the_silver_view(): void
    {
        $this->tag('SilverRing', Occasion::Yalda->value, 'silver');

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertStringContainsString('metal=silver', $slots[0]);
    }

    public function test_the_cell_of_a_campaign_shows_its_title(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        Campaign::factory()->occasions([Occasion::Yalda->value])->create(['name' => 'Yalda Offers']);

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertSee('Yalda Offers');
    }

    public function test_included_products_are_pinned_into_the_cell(): void
    {
        $this->tag('BirthdayRing', Occasion::Birthday->value);

        $hero = Product::factory()->create(['name' => 'HeroRing']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $campaign->productLinks()->create([
            'product_id' => $hero->id,
            'role' => CampaignProductRole::Include,
        ]);

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertStringContainsString('HeroRing', $slots[0]);
    }

    public function test_an_excluded_product_is_missing_from_the_cell(): void
    {
        $blocked = $this->tag('BlockedRing', Occasion::Birthday->value);
        $this->tag('KeptRing', Occasion::Birthday->value);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $campaign->productLinks()->create([
            'product_id' => $blocked->id,
            'role' => CampaignProductRole::Exclude,
        ]);

        $slots = $this->campaignSlots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertStringContainsString('KeptRing', $slots[0]);
        $this->assertStringNotContainsString('BlockedRing', $slots[0]);
    }

    public function test_editing_a_campaign_refreshes_the_home_page_immediately(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create(['name' => 'Yalda Offers']);

        $this->get(route('client.welcome'))->assertSee('Yalda Offers');

        $campaign->update(['name' => 'Chaharshanbe Offers']);

        $this->get(route('client.welcome'))
            ->assertOk()
            ->assertSee('Chaharshanbe Offers')
            ->assertDontSee('Yalda Offers');
    }

    public function test_the_campaign_page_lists_the_whole_collection(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);
        $this->tag('YaldaNecklace', Occasion::Yalda->value);

        $campaign = Campaign::factory()
            ->occasions([Occasion::Yalda->value])
            ->create(['name' => 'Yalda Offers', 'subtitle' => 'Longest night of the year']);

        $this->get($campaign->url('gold'))
            ->assertOk()
            ->assertSee('Yalda Offers')
            ->assertSee('Longest night of the year')
            ->assertSee('YaldaRing')
            ->assertSee('YaldaNecklace');
    }

    public function test_the_campaign_page_of_a_finished_campaign_is_gone(): void
    {
        $this->tag('YaldaRing', Occasion::Yalda->value);

        $campaign = Campaign::factory()->ended()->occasions([Occasion::Yalda->value])->create();

        $this->get(route('client.products', ['campaign' => $campaign->slug]))->assertNotFound();
    }
}
