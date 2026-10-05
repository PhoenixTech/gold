<?php

namespace Tests\Unit;

use App\Enums\Occasion;
use App\Models\Campaign;
use App\Services\CampaignResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which campaign owns the single event slot of the home page, per metal.
 */
class CampaignResolverTest extends TestCase
{
    use RefreshDatabase;

    private CampaignResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(CampaignResolver::class);
    }

    public function test_no_campaign_means_an_empty_slot(): void
    {
        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_a_published_campaign_inside_its_window_owns_the_slot(): void
    {
        $campaign = Campaign::factory()->create();

        $this->assertTrue($this->resolver->forMetal('gold')->is($campaign));
        $this->assertTrue($this->resolver->forMetal('silver')->is($campaign));
    }

    public function test_a_draft_campaign_never_owns_the_slot(): void
    {
        Campaign::factory()->draft()->create();

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_a_disabled_campaign_never_owns_the_slot(): void
    {
        Campaign::factory()->disabled()->create();

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_a_future_campaign_waits_for_its_start(): void
    {
        Campaign::factory()->scheduled(now()->addDay())->create();

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_an_ended_campaign_releases_the_slot(): void
    {
        Campaign::factory()->ended()->create();

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_the_end_bound_is_exclusive(): void
    {
        Campaign::factory()->create(['ends_at' => now()]);

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_an_endless_campaign_stays_on_the_slot(): void
    {
        $campaign = Campaign::factory()->endless()->create();

        $this->assertTrue($this->resolver->forMetal('gold')->is($campaign));
    }

    public function test_the_highest_priority_campaign_wins_an_overlap(): void
    {
        $low = Campaign::factory()->priority(5)->occasions([Occasion::Yalda->value])->create();
        $high = Campaign::factory()->priority(50)->occasions([Occasion::Birthday->value])->create();

        $this->assertTrue($this->resolver->forMetal('gold')->is($high));
        $this->assertFalse($this->resolver->forMetal('gold')->is($low));
    }

    public function test_a_priority_tie_is_broken_by_the_newest_campaign(): void
    {
        $older = Campaign::factory()->priority(10)->create();
        $newer = Campaign::factory()->priority(10)->create();

        $this->assertTrue($this->resolver->forMetal('gold')->is($newer));
        $this->assertFalse($this->resolver->forMetal('gold')->is($older));
    }

    public function test_each_metal_tab_can_show_a_different_campaign(): void
    {
        $gold = Campaign::factory()->goldOnly()->create(['name' => 'GoldEvent']);
        $silver = Campaign::factory()->create(['metal_scope' => ['silver'], 'name' => 'SilverEvent']);

        $this->assertTrue($this->resolver->forMetal('gold')->is($gold));
        $this->assertTrue($this->resolver->forMetal('silver')->is($silver));
    }

    public function test_a_gold_only_campaign_leaves_the_silver_tab_empty(): void
    {
        Campaign::factory()->goldOnly()->create();

        $this->assertNotNull($this->resolver->forMetal('gold'));
        $this->assertNull($this->resolver->forMetal('silver'));
    }

    public function test_a_high_priority_campaign_on_another_metal_does_not_block_the_slot(): void
    {
        Campaign::factory()->goldOnly()->priority(90)->create();

        $silver = Campaign::factory()->create([
            'metal_scope' => ['silver'],
            'priority' => 1,
        ]);

        $this->assertTrue($this->resolver->forMetal('silver')->is($silver));
    }

    public function test_a_cash_only_scope_does_not_match_any_tab(): void
    {
        Campaign::factory()->create(['metal_scope' => ['platinum']]);

        $this->assertNull($this->resolver->forMetal('gold'));
        $this->assertNull($this->resolver->forMetal('silver'));
    }

    public function test_a_soft_deleted_campaign_releases_the_slot(): void
    {
        $campaign = Campaign::factory()->create();
        $campaign->delete();

        $this->assertNull($this->resolver->forMetal('gold'));
    }

    public function test_for_home_returns_one_entry_per_tab(): void
    {
        Campaign::factory()->create();

        $this->assertSame(['gold', 'silver'], array_keys($this->resolver->forHome()));
    }
}
