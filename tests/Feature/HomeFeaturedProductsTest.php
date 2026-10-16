<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Services\FeaturedProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Tags\Tag;
use Tests\TestCase;

class HomeFeaturedProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The settings service keeps an in-process cache that survives between
        // test cases, so it has to be dropped for freshly created settings.
        clearSettingsCache();
    }

    protected function tearDown(): void
    {
        clearSettingsCache();

        parent::tearDown();
    }

    private function productTag(string $name): Tag
    {
        return Tag::findOrCreate($name, FeaturedProductsService::TAG_TYPE);
    }

    private function selectTags(Tag ...$tags): void
    {
        $ids = json_encode(array_map(fn (Tag $tag) => $tag->id, $tags));

        Setting::updateOrCreate(
            ['key' => FeaturedProductsService::SETTING_KEY],
            [
                'section' => 'Homepage',
                'type' => 'TAG_SET',
                'title' => 'Home page featured product tags',
                'value' => $ids,
                'raw' => $ids,
                'size' => 12,
            ]
        );
    }

    public function test_home_section_renders_three_tag_boxes_with_labels_and_icons(): void
    {
        $special = $this->productTag('خاص');
        $weight = $this->productTag('گرمی');
        $bestseller = $this->productTag('پرفروش');

        Product::factory()->create(['name' => 'DiamondRing'])->attachTag($special);
        Product::factory()->create(['name' => 'GramBangle'])->attachTag($weight);
        Product::factory()->create(['name' => 'HotNecklace'])->attachTag($bestseller);

        $this->selectTags($special, $weight, $bestseller);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('featured-tag-box');
        $response->assertSee('خاص‌ترین‌ها');
        $response->assertSee('زیر یک‌گرمی‌ها');
        $response->assertSee('پرفروش‌ترین‌ها');
        $response->assertSee('ri-vip-diamond-line');
        $response->assertSee('ri-scales-3-line');
        $response->assertSee('ri-fire-line');
        $response->assertSee(route('client.tag', $special->slug));
        $response->assertSee(route('client.tag', $weight->slug));
        $response->assertSee(route('client.tag', $bestseller->slug));
        $response->assertDontSee('DiamondRing');
        $response->assertDontSee('GramBangle');
        $response->assertDontSee('HotNecklace');
    }

    public function test_home_section_uses_at_most_three_tags(): void
    {
        $first = $this->productTag('AlphaTag');
        $second = $this->productTag('BetaTag');
        $third = $this->productTag('GammaTag');
        $ignored = $this->productTag('DeltaTag');
        $tags = [$first, $second, $third, $ignored];

        $this->selectTags(...$tags);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('AlphaTag');
        $response->assertSee('BetaTag');
        $response->assertSee('GammaTag');
        $response->assertDontSee('DeltaTag');
    }

    public function test_home_section_is_hidden_without_tags(): void
    {
        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertDontSee('featured-tags-nav');
    }

    public function test_home_navbar_shows_persian_gold_price_without_credit_tag(): void
    {
        Setting::updateOrCreate(
            ['key' => 'gold'],
            [
                'section' => 'General',
                'type' => 'TEXT',
                'title' => 'Gold price',
                'ltr' => true,
                'value' => '6750000',
                'raw' => '6750000',
            ]
        );

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('۶,۷۵۰,۰۰۰');
        // The credit link is gone from the navbar (the drawer entry keeps its own markup).
        $response->assertDontSee('ri-trophy-line fs-18', false);
    }
}
