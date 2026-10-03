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

    public function test_home_section_shows_only_products_carrying_the_selected_tags(): void
    {
        $tagged = Product::factory()->create(['name' => 'TaggedRing', 'category_id' => Category::factory()]);
        $alsoTagged = Product::factory()->create(['name' => 'SecondTaggedRing']);
        $untagged = Product::factory()->create(['name' => 'PlainRing']);

        $tag = $this->productTag('Special');
        $tagged->attachTag($tag);
        $alsoTagged->attachTag($tag);

        $this->selectTags($tag);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('TaggedRing');
        $response->assertSee('SecondTaggedRing');
        $response->assertDontSee('PlainRing');
    }

    public function test_home_section_ignores_unpublished_products_of_the_selected_tags(): void
    {
        $published = Product::factory()->create(['name' => 'PublishedRing']);
        $draft = Product::factory()->create(['name' => 'DraftRing', 'status' => 0]);

        $tag = $this->productTag('Special');
        $published->attachTag($tag);
        $draft->attachTag($tag);

        $this->selectTags($tag);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('PublishedRing');
        $response->assertDontSee('DraftRing');
    }

    public function test_home_section_uses_at_most_three_tags(): void
    {
        $first = $this->productTag('One');
        $second = $this->productTag('Two');
        $third = $this->productTag('Three');
        $ignored = $this->productTag('Four');
        $tags = [$first, $second, $third, $ignored];

        $names = ['FirstRing', 'SecondRing', 'ThirdRing', 'IgnoredRing'];

        foreach ($names as $index => $name) {
            Product::factory()->create(['name' => $name])->attachTag($tags[$index]);
        }

        $this->selectTags(...$tags);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('FirstRing');
        $response->assertSee('SecondRing');
        $response->assertSee('ThirdRing');
        $response->assertDontSee('IgnoredRing');
    }

    public function test_home_section_falls_back_to_the_newest_products_without_a_tag(): void
    {
        Product::factory()->create(['name' => 'NewestRing']);
        Product::factory()->create(['name' => 'OlderRing']);

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee('NewestRing');
        $response->assertSee('OlderRing');
    }

    public function test_home_section_is_hidden_when_nothing_matches(): void
    {
        Product::factory()->create(['name' => 'SomeRing']);

        $this->selectTags($this->productTag('Unused'));

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertDontSee('SomeRing');
        $response->assertDontSee('featured-products');
    }

    public function test_home_section_heading_has_no_view_all_link(): void
    {
        Product::factory()->create();

        $response = $this->get(route('client.welcome'));

        $response->assertOk();
        $response->assertSee(__('Our Featured Products'));
        $response->assertSee(__('A curated selection of our finest gold & jewelry pieces'));
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
