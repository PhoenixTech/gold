<?php

namespace Tests\Feature\Admin;

use App\Enums\CampaignStatus;
use App\Enums\Occasion;
use App\Models\Campaign;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The campaign form is a single full-width column of stacked fields organised
 * into Bootstrap tabs. It deliberately has no col-lg-3 sidebar like the older
 * forms in this panel, because a campaign is one linear workflow rather than a
 * permanent settings column.
 */
class AdminCampaignFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        App::setLocale('en');

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $admin->assignRole('admin');
        $this->actingAs($admin);
    }

    private function html(string $url): string
    {
        return $this->get($url)->assertOk()->getContent();
    }

    /**
     * Just the campaign form, so assertions are not confused by the panel
     * chrome (sidebar, breadcrumb, attachs drawer) around it.
     */
    private function formHtml(string $url): string
    {
        $html = $this->html($url);

        preg_match('/<form[^>]*id="model-form-(create|edit)".*?<\/form>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'The campaign form element was not found');

        return $matches[0];
    }

    private function campaign(): Campaign
    {
        return Campaign::factory()
            ->occasions([Occasion::Yalda->value])
            ->create(['name' => 'Yalda Offers']);
    }

    /**
     * @return list<string>
     */
    private function tabIds(string $html): array
    {
        preg_match_all('/data-bs-target="#(tab-[a-z]+)"/', $html, $matches);

        return array_values(array_unique($matches[1]));
    }

    public function test_the_form_uses_tabs_instead_of_a_sidebar(): void
    {
        $form = $this->formHtml(route('admin.campaign.create'));

        $this->assertStringContainsString('nav nav-tabs', $form);
        $this->assertStringContainsString('data-bs-toggle="tab"', $form);

        // The old two-column layout put the fields in a col-lg-9 next to a
        // col-lg-3 sidebar. Neither may come back. (The occasions checkbox grid
        // legitimately uses col-6 col-sm-4 col-lg-3 cells, so match the exact
        // layout class rather than the bare token.)
        $this->assertStringNotContainsString('class="col-lg-3"', $form);
        $this->assertStringNotContainsString('class="col-lg-9"', $form);
        $this->assertStringNotContainsString('class="col-3"', $form);
    }

    public function test_it_exposes_every_workflow_tab(): void
    {
        $this->assertSame(
            ['tab-content', 'tab-products', 'tab-schedule', 'tab-publishing'],
            $this->tabIds($this->html(route('admin.campaign.create')))
        );
    }

    public function test_each_tab_has_a_matching_pane(): void
    {
        $html = $this->html(route('admin.campaign.create'));

        foreach ($this->tabIds($html) as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html, "Missing pane for #{$id}");
            $this->assertMatchesRegularExpression(
                '/class="tab-pane fade[^"]*" id="'.preg_quote($id, '/').'" role="tabpanel"/',
                $html,
                "Pane #{$id} is not a Bootstrap tab pane"
            );
        }
    }

    public function test_only_the_first_tab_is_open_on_a_fresh_form(): void
    {
        $html = $this->formHtml(route('admin.campaign.create'));

        $this->assertStringContainsString('id="tab-content" role="tabpanel"', $html);
        $this->assertMatchesRegularExpression(
            '/class="tab-pane fade show active" id="tab-content"/',
            $html
        );
    }

    public function test_fields_are_stacked_rather_than_split_into_columns(): void
    {
        $html = $this->html(route('admin.campaign.create'));

        // A stacked form-group per field; the only .row is the occasions grid.
        $this->assertGreaterThanOrEqual(
            12,
            substr_count($html, 'class="form-group'),
            'Expected the fields to be stacked as individual form groups'
        );
    }

    public function test_every_input_lives_inside_a_tab_pane(): void
    {
        $campaign = $this->campaign();

        $html = $this->html(route('admin.campaign.edit', $campaign->slug));

        preg_match('/<div class="tab-content p-3">(.*?)<\/form>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'The tab-content block was not found');

        foreach (['name="name"', 'name="status"', 'name="starts_at"', 'name="occasions[]"'] as $field) {
            $this->assertStringContainsString($field, $matches[1], "{$field} is outside the tabs");
        }
    }

    public function test_the_action_bar_sits_outside_the_panels_so_it_is_reachable_from_any_tab(): void
    {
        $html = $this->html(route('admin.campaign.create'));

        // The submit button is a sibling of the tab panes, not inside one, so it
        // stays visible whichever tab is open.
        $this->assertMatchesRegularExpression(
            '/<div class="p-3 border-top.*?type="submit".*?<\/div>\s*<\/div>\s*<\/div>/s',
            $html
        );
    }

    public function test_a_failed_validation_opens_the_tab_holding_the_error(): void
    {
        // A bad end date is validated on the Schedule tab; without help the admin
        // would be left staring at the Content tab.
        $this->post(route('admin.campaign.store'), [
            'name' => 'Yalda Offers',
            'status' => CampaignStatus::Published->value,
            'starts_at' => now()->addWeek()->toDateTimeString(),
            'ends_at' => now()->subWeek()->toDateTimeString(),
        ])->assertSessionHasErrors('ends_at');

        $html = $this->html(route('admin.campaign.create'));

        $this->assertStringContainsString('window.bootstrap.Tab.getOrCreateInstance(trigger).show()', $html);
        $this->assertStringContainsString("closest('.tab-pane')", $html);
    }

    public function test_the_schedule_collision_warning_sits_above_the_tabs(): void
    {
        $winner = Campaign::factory()->priority(90)->create(['name' => 'Christmas Event']);

        $loser = Campaign::factory()->priority(10)->create(['name' => 'Yalda Event']);

        $html = $this->html(route('admin.campaign.edit', $loser->slug));

        $this->assertStringContainsString('Christmas Event', $html);
        $this->assertLessThan(
            strpos($html, 'nav nav-tabs'),
            strpos($html, 'Christmas Event'),
            'The collision warning must be visible without switching tabs'
        );
    }

    public function test_the_edit_form_is_prefilled(): void
    {
        $product = Product::factory()->create();

        $campaign = Campaign::factory()->occasions([Occasion::Yalda->value])->create([
            'name' => 'Yalda Offers',
            'subtitle' => 'Longest night',
            'badge_text' => 'Special Offer',
            'priority' => 77,
        ]);

        $campaign->productLinks()->create(['product_id' => $product->id, 'role' => 'include']);

        $html = $this->html(route('admin.campaign.edit', $campaign->slug));

        $this->assertStringContainsString('value="Yalda Offers"', $html);
        $this->assertStringContainsString('value="Longest night"', $html);
        $this->assertStringContainsString('value="Special Offer"', $html);
        $this->assertStringContainsString('value="77"', $html);
        $this->assertStringContainsString((string) $product->id, $html);
        $this->assertStringContainsString('checked', $html);
    }

    public function test_every_translatable_label_of_the_form_exists_in_fa_json(): void
    {
        $fa = json_decode(file_get_contents(resource_path('lang/fa.json')), true);

        // The form is split across sub-pages, so scan the whole directory.
        $files = array_merge(
            glob(resource_path('views/admin/campaigns/*.blade.php')),
            glob(resource_path('views/admin/campaigns/sub-pages/*.blade.php')),
        );

        $this->assertNotEmpty($files);

        $missing = [];

        foreach ($files as $file) {
            preg_match_all("/(?:__|@lang)\(\s*[\x27\x22]([^\x27\x22]+)[\x27\x22]\s*[\),]/", file_get_contents($file), $matches);

            foreach (array_unique($matches[1]) as $key) {
                if (! str_contains($key, '$') && ! array_key_exists($key, $fa)) {
                    $missing[$key] = basename($file);
                }
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Missing Persian translations: '.json_encode($missing, JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_the_form_does_not_query_a_column_that_does_not_exist(): void
    {
        $this->assertFalse(Schema::hasColumn('campaigns', 'schedule'));
        $this->assertTrue(Schema::hasColumn('campaigns', 'starts_at'));
    }
}
