<?php

namespace Tests\Unit;

use App\Enums\CampaignProductRole;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Product;
use App\Services\CampaignProductSource;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * The pipeline behind a campaign's product set:
 *
 *   final = INCLUDE (admin order) + occasion matches − EXCLUDE
 *
 * `CampaignProductSource::ids()` is the single source of truth; the tile, the
 * campaign page and the admin preview all project from it.
 */
class CampaignProductSourceTest extends TestCase
{
    use RefreshDatabase;

    private CampaignProductSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        $this->source = app(CampaignProductSource::class);
    }

    private function product(string $name, array $occasions = [], string $metal = 'gold'): Product
    {
        return Product::factory()->create([
            'name' => $name,
            'metal_type' => $metal,
            'occasions' => $occasions,
        ]);
    }

    /**
     * @return list<int>
     */
    private function ids(Campaign $campaign, string $metal = 'gold'): array
    {
        return $this->source->ids($campaign, $metal)->all();
    }

    /**
     * @return list<int>
     */
    private function tileIds(Campaign $campaign, string $metal = 'gold'): array
    {
        return $this->source->tile($campaign, $metal)->pluck('id')->values()->all();
    }

    public function test_it_returns_only_products_carrying_the_selected_occasion(): void
    {
        $match = $this->product('YaldaRing', ['yalda']);
        $this->product('BirthdayRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $this->assertSame([$match->id], $this->ids($campaign));
    }

    public function test_multiple_occasions_are_combined_with_or_semantics(): void
    {
        $birthday = $this->product('BirthdayRing', ['birthday']);
        $anniversary = $this->product('AnniversaryRing', ['anniversary']);
        $this->product('WeddingRing', ['wedding']);

        $campaign = Campaign::factory()
            ->occasions([Occasion::Birthday->value, Occasion::Anniversary->value])
            ->create();

        $this->assertEqualsCanonicalizing([$birthday->id, $anniversary->id], $this->ids($campaign));
    }

    public function test_a_product_matching_two_selected_occasions_is_returned_once(): void
    {
        $both = $this->product('GiftRing', ['birthday', 'anniversary']);

        $campaign = Campaign::factory()
            ->occasions([Occasion::Birthday->value, Occasion::Anniversary->value])
            ->create();

        $this->assertSame([$both->id], $this->ids($campaign));
    }

    public function test_included_products_come_first_in_the_admin_order(): void
    {
        $newest = $this->product('NewerRing', ['birthday']);
        $older = $this->product('OlderRing', ['birthday']);
        $hero = $this->product('HeroRing', ['birthday']);
        $second = $this->product('SecondRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $campaign->productLinks()->createMany([
            ['product_id' => $second->id, 'role' => CampaignProductRole::Include, 'sort' => 2],
            ['product_id' => $hero->id, 'role' => CampaignProductRole::Include, 'sort' => 1],
        ]);

        $ids = $this->ids($campaign);

        $this->assertSame([$hero->id, $second->id], array_slice($ids, 0, 2));
        // The occasion matches follow, newest first.
        $this->assertEqualsCanonicalizing([$newest->id, $older->id], array_slice($ids, 2));
    }

    public function test_a_product_cannot_be_both_included_and_excluded(): void
    {
        $blocked = $this->product('BlockedRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $campaign->productLinks()->create([
            'product_id' => $blocked->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        $campaign->productLinks()->create([
            'product_id' => $blocked->id,
            'role' => CampaignProductRole::Exclude,
        ]);
    }

    public function test_rolling_a_product_from_included_to_excluded_removes_it(): void
    {
        $kept = $this->product('KeptRing', ['birthday']);
        $rolled = $this->product('RolledRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        // A model update fires the pivot's saved hook, which is what retires the
        // cached set. (A query-builder update would not, which is why the admin
        // write path goes through CampaignService::syncProductLinks.)
        $link = $campaign->productLinks()->create([
            'product_id' => $rolled->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->assertEqualsCanonicalizing([$kept->id, $rolled->id], $this->ids($campaign));

        $link->update(['role' => CampaignProductRole::Exclude]);

        $this->assertSame([$kept->id], $this->ids($campaign));
    }

    public function test_exclusion_beats_the_occasion_match(): void
    {
        $kept = $this->product('KeptRing', ['birthday']);
        $blocked = $this->product('BlockedRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();
        $campaign->productLinks()->create([
            'product_id' => $blocked->id,
            'role' => CampaignProductRole::Exclude,
        ]);

        $this->assertSame([$kept->id], $this->ids($campaign));
    }

    public function test_products_are_filtered_by_the_current_metal(): void
    {
        $gold = $this->product('GoldRing', ['birthday'], 'gold');
        $silver = $this->product('SilverRing', ['birthday'], 'silver');

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $this->assertSame([$gold->id], $this->ids($campaign, 'gold'));
        $this->assertSame([$silver->id], $this->ids($campaign, 'silver'));
    }

    public function test_an_included_product_from_another_metal_is_skipped(): void
    {
        $silver = $this->product('SilverRing', [], 'silver');

        $campaign = Campaign::factory()->create();
        $campaign->productLinks()->create([
            'product_id' => $silver->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->assertSame([], $this->ids($campaign, 'gold'));
    }

    public function test_unpublished_products_are_never_resolved(): void
    {
        $this->product('DraftRing', ['birthday'])->update(['status' => 0]);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $this->assertSame([], $this->ids($campaign));
    }

    public function test_an_unpublished_included_product_drops_out(): void
    {
        $live = $this->product('LiveRing', ['birthday']);
        $draft = $this->product('DraftRing', ['birthday']);
        $draft->update(['status' => 0]);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();
        $campaign->productLinks()->create([
            'product_id' => $draft->id,
            'role' => CampaignProductRole::Include,
            'sort' => 0,
        ]);

        // The draft is not pinned, and it does not reappear through its occasion.
        $this->assertSame([$live->id], $this->ids($campaign));
    }

    public function test_the_tile_is_capped_at_the_campaign_limit(): void
    {
        foreach (range(1, 5) as $i) {
            $this->product("Ring{$i}", ['birthday']);
        }

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create(['limit' => 2]);

        $this->assertCount(5, $this->ids($campaign));
        $this->assertCount(2, $this->tileIds($campaign));
    }

    public function test_it_falls_back_to_included_products_when_no_occasion_is_selected(): void
    {
        $hero = $this->product('HeroRing');
        $this->product('UntaggedRing');

        $campaign = Campaign::factory()->occasions([])->create();
        $campaign->productLinks()->create([
            'product_id' => $hero->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->assertSame([$hero->id], $this->ids($campaign));
    }

    public function test_no_occasions_and_no_inclusions_resolves_to_an_empty_set(): void
    {
        $this->product('AnyRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([])->create();

        $this->assertSame([], $this->ids($campaign));
        $this->assertTrue($this->source->tile($campaign, 'gold')->isEmpty());
    }

    public function test_the_tile_is_eager_loaded_and_in_campaign_order(): void
    {
        $hero = $this->product('HeroRing', ['birthday']);
        $this->product('OtherRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();
        $campaign->productLinks()->create(['product_id' => $hero->id, 'role' => CampaignProductRole::Include]);

        $tile = $this->source->tile($campaign, 'gold');

        $this->assertSame($hero->id, $tile->first()->id);
        $this->assertTrue($tile->first()->relationLoaded('category'));
    }

    public function test_the_order_map_matches_the_id_order(): void
    {
        $older = $this->product('FirstRing', ['birthday']);
        $newer = $this->product('SecondRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $map = $this->source->orderMap($campaign, 'gold');

        $this->assertSame($this->ids($campaign), array_keys($map));
        // Occasion matches come newest first, so the newer product is index 0.
        $this->assertSame(0, $map[$newer->id]);
        $this->assertSame(1, $map[$older->id]);
    }

    public function test_the_query_paginates_the_campaign_set(): void
    {
        foreach (range(1, 4) as $i) {
            $this->product("Ring{$i}", ['birthday']);
        }

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $page = $this->source->query($campaign, 'gold')->paginate(2);

        $this->assertSame(4, $page->total());
        $this->assertCount(2, $page->items());
    }

    public function test_the_query_excludes_products_of_the_other_metal(): void
    {
        $this->product('GoldRing', ['birthday'], 'gold');
        $this->product('SilverRing', ['birthday'], 'silver');

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $this->assertSame(1, $this->source->query($campaign, 'gold')->count());
        $this->assertSame(1, $this->source->query($campaign, 'silver')->count());
    }

    public function test_the_preview_reports_every_bucket_from_one_source(): void
    {
        $hero = $this->product('HeroRing', ['birthday']);
        $blocked = $this->product('BlockedRing', ['birthday']);
        $auto = $this->product('AutoRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $campaign->productLinks()->createMany([
            ['product_id' => $hero->id, 'role' => CampaignProductRole::Include, 'sort' => 1],
            ['product_id' => $blocked->id, 'role' => CampaignProductRole::Exclude],
        ]);

        $preview = $this->source->preview($campaign, 'gold');

        $this->assertSame(2, $preview['final']);
        $this->assertSame(1, $preview['pinned']);
        $this->assertSame(1, $preview['occasions']);
        $this->assertSame(1, $preview['excluded']);
        $this->assertSame([$hero->id], $preview['pinnedIds']);
        // Pinned first, then the occasion match.
        $this->assertSame([$hero->id, $auto->id], $preview['products']->pluck('id')->values()->all());
    }

    public function test_the_preview_counts_agree_with_the_tile(): void
    {
        foreach (range(1, 4) as $i) {
            $this->product("Ring{$i}", ['birthday']);
        }

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create(['limit' => 3]);

        $preview = $this->source->preview($campaign, 'gold');

        $this->assertSame(3, $preview['final']);
        $this->assertCount(3, $preview['products']);
        $this->assertSame(
            $this->tileIds($campaign),
            $preview['products']->pluck('id')->values()->all()
        );
    }

    public function test_the_preview_never_marks_an_occasion_match_as_pinned(): void
    {
        $this->product('AutoRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $preview = $this->source->preview($campaign, 'gold');

        $this->assertSame(0, $preview['pinned']);
        $this->assertSame([], $preview['pinnedIds']);
        $this->assertSame(1, $preview['occasions']);
    }

    public function test_editing_the_inclusion_list_retires_the_cached_set(): void
    {
        $first = $this->product('FirstRing', ['birthday']);
        $second = $this->product('SecondRing', ['birthday']);

        $campaign = Campaign::factory()->occasions([])->create();

        $this->assertSame([], $this->ids($campaign));

        $campaign->productLinks()->create([
            'product_id' => $first->id,
            'role' => CampaignProductRole::Include,
        ]);

        $this->assertSame([$first->id], $this->ids($campaign));

        $campaign->productLinks()->create([
            'product_id' => $second->id,
            'role' => CampaignProductRole::Include,
            'sort' => 1,
        ]);

        $this->assertSame([$first->id, $second->id], $this->ids($campaign));
    }

    public function test_changing_a_product_occasion_retires_the_cached_set(): void
    {
        $product = $this->product('YaldaRing', ['yalda']);

        $campaign = Campaign::factory()->occasions([Occasion::Birthday->value])->create();

        $this->assertSame([], $this->ids($campaign));

        $product->update(['occasions' => ['birthday']]);

        $this->assertSame([$product->id], $this->ids($campaign));
    }

    public function test_the_cached_set_is_a_plain_collection_of_ids(): void
    {
        $this->product('YaldaRing', ['yalda']);

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create();

        $ids = $this->source->ids($campaign, 'gold');

        $this->assertInstanceOf(Collection::class, $ids);
        $this->assertIsInt($ids->first());
    }
}
