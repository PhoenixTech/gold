<?php

namespace Tests\Feature;

use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use App\Services\CampaignProductSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Campaign tile artwork, and the combined product set (included + occasions -
 * excluded) that the tile and the landing page share.
 */
class HomeCampaignArtworkTest extends TestCase
{
    use RefreshDatabase;

    private function tag(string $name, array $occasions, string $metal = 'gold'): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'metal_type' => $metal,
            'occasions' => $occasions,
        ]);
    }

    /**
     * @return list<string>
     */
    private function slots(string $html): array
    {
        $chunks = preg_split('/<div class="col-3 text-center mb-3[^"]*">/', $html);

        return array_values(array_filter(
            $chunks,
            static fn (string $chunk) => str_contains($chunk, 'campaign-slot__box')
        ));
    }

    public function test_it_renders_product_thumbnails_when_no_artwork_is_uploaded(): void
    {
        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $slots = $this->slots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertStringContainsString('campaign-slot__thumbs', $slots[0]);
        $this->assertStringNotContainsString('campaign-slot__art', $slots[0]);
    }

    public function test_uploaded_artwork_replaces_the_product_thumbnails(): void
    {
        Storage::fake('public');

        $this->tag('YaldaRing', [Occasion::Yalda->value]);

        Campaign::factory()->occasions([Occasion::Yalda->value])->create([
            'image' => 'yalda.webp',
            'mobile_image' => 'yalda-mobile.webp',
        ]);

        $slots = $this->slots($this->get(route('client.welcome'))->assertOk()->getContent());

        $this->assertStringContainsString('campaign-slot__art', $slots[0]);
        $this->assertStringNotContainsString('campaign-slot__thumbs', $slots[0]);

        // The mobile artwork is offered through a <source> for small screens.
        $this->assertStringContainsString('<source media="(max-width: 575.98px)"', $slots[0]);
        $this->assertStringContainsString('yalda-mobile.webp', $slots[0]);
    }

    public function test_the_artwork_is_served_from_the_optimized_variant(): void
    {
        $campaign = Campaign::factory()->create(['image' => 'yalda.webp']);

        $this->assertStringContainsString('campaigns/optimized-yalda.webp', (string) $campaign->imgUrl());
        $this->assertStringNotContainsString('optimized-', (string) $campaign->imgOriginalUrl());
    }

    public function test_mobile_artwork_falls_back_to_the_desktop_one(): void
    {
        $campaign = Campaign::factory()->create(['image' => 'yalda.webp']);

        $this->assertSame($campaign->imgUrl(), $campaign->mobileImgUrl());
    }

    public function test_the_admin_can_upload_tile_artwork(): void
    {
        Storage::fake('public');

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.campaign.update', $campaign->slug), [
                'name' => 'Yalda Offers',
                'status' => 'published',
                'occasions' => [Occasion::Yalda->value],
                'image' => UploadedFile::fake()->image('yalda.jpg'),
            ])
            ->assertRedirect();

        $this->assertNotNull($campaign->fresh()->image);
    }

    /*
    |--------------------------------------------------------------------------
    | The combined product set
    |--------------------------------------------------------------------------
    */

    public function test_the_tile_shows_included_products_combined_with_occasion_matches(): void
    {
        $auto = $this->tag('AutoRing', [Occasion::Yalda->value]);
        $hero = Product::factory()->create(['name' => 'HeroRing', 'occasions' => []]);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $campaign->productLinks()->create(['product_id' => $hero->id, 'role' => 'include', 'sort' => 0]);

        $resolved = app(CampaignProductSource::class)->tile($campaign, 'gold');

        // Included first, occasion match second.
        $this->assertSame([$hero->id, $auto->id], $resolved->pluck('id')->all());
    }

    public function test_excluded_products_are_removed_from_the_combined_set(): void
    {
        $kept = $this->tag('KeptRing', [Occasion::Yalda->value]);
        $blocked = $this->tag('BlockedRing', [Occasion::Yalda->value]);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();
        $campaign->productLinks()->create(['product_id' => $blocked->id, 'role' => 'exclude']);

        $resolved = app(CampaignProductSource::class)->tile($campaign, 'gold');

        $this->assertSame([$kept->id], $resolved->pluck('id')->all());
    }

    public function test_the_preview_labels_where_each_product_came_from(): void
    {
        $auto = $this->tag('AutoRing', [Occasion::Yalda->value]);
        $hero = Product::factory()->create(['name' => 'HeroRing', 'occasions' => []]);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();
        $campaign->productLinks()->create(['product_id' => $hero->id, 'role' => 'include', 'sort' => 0]);

        $preview = app(CampaignProductSource::class)->preview($campaign, 'gold');

        $this->assertSame(2, $preview['final']);
        $this->assertSame(1, $preview['occasions']);
        $this->assertSame(1, $preview['pinned']);

        // The form labels each chip by origin; pinnedIds is the source of truth.
        $this->assertSame([$hero->id], $preview['pinnedIds']);
        $this->assertNotContains($auto->id, $preview['pinnedIds']);
    }

    public function test_the_campaign_page_shows_the_same_combined_set_as_the_tile(): void
    {
        $this->tag('AutoRing', [Occasion::Yalda->value]);
        $hero = Product::factory()->create(['name' => 'HeroRing', 'occasions' => []]);
        $blocked = $this->tag('BlockedRing', [Occasion::Yalda->value]);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $campaign->productLinks()->createMany([
            ['product_id' => $hero->id, 'role' => 'include', 'sort' => 0],
            ['product_id' => $blocked->id, 'role' => 'exclude'],
        ]);

        $this->get($campaign->url('gold'))
            ->assertOk()
            ->assertSee('HeroRing')
            ->assertSee('AutoRing')
            ->assertDontSee('BlockedRing');
    }

    /*
    |--------------------------------------------------------------------------
    | The tile destination
    |--------------------------------------------------------------------------
    | A campaign has exactly one destination: its own product page. There is no
    | link-type concept, because the campaign page renders the exact combined
    | set, so any other target would advertise something the tile does not show.
    */

    public function test_the_campaign_url_is_the_catalog_scoped_to_the_campaign(): void
    {
        $campaign = Campaign::factory()->create(['slug' => 'yalda-offers']);

        $this->assertSame(
            route('client.products', ['campaign' => 'yalda-offers']),
            $campaign->url()
        );
    }

    public function test_the_url_carries_the_metal_when_one_is_given(): void
    {
        $campaign = Campaign::factory()->create(['slug' => 'yalda-offers']);

        $this->assertSame(
            route('client.products', ['campaign' => 'yalda-offers', 'metal' => 'silver']),
            $campaign->url('silver')
        );
    }

    public function test_the_url_never_throws_whatever_the_campaign_contains(): void
    {
        // Rendering the home page must not be able to 500 because of a campaign.
        $campaign = Campaign::factory()->occasions([])->create(['slug' => 'hand-picked']);

        $this->assertStringStartsWith(route('client.products'), $campaign->url());
        $this->assertStringStartsWith(route('client.products'), $campaign->url('gold'));
    }
}
