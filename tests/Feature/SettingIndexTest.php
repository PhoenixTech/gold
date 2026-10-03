<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\FeaturedProductsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Tags\Tag;
use Tests\TestCase;

class SettingIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_data_returns_empty_array_when_data_is_null(): void
    {
        $setting = Setting::factory()->number()->create([
            'data' => null,
        ]);

        $this->assertSame([], $setting->getData());
    }

    public function test_get_data_returns_decoded_attributes_when_json_is_present(): void
    {
        $setting = Setting::factory()->number()->create([
            'data' => json_encode(['xmin' => 1, 'xmax' => 10]),
        ]);

        $this->assertSame(['xmin' => 1, 'xmax' => 10], $setting->fresh()->getData());
    }

    public function test_get_data_returns_empty_array_when_json_is_invalid(): void
    {
        $setting = Setting::factory()->number()->create([
            'data' => 'not-json',
        ]);

        $this->assertSame([], $setting->fresh()->getData());
    }

    public function test_legacy_market_and_bank_settings_are_hidden_from_the_settings_field(): void
    {
        foreach ([
            'gold',
            'gold24',
            'silver',
            'dollar',
            'bank_card_number',
            'bank_sheba',
            'bank_account_name',
        ] as $key) {
            $setting = Setting::factory()->create(['key' => $key]);

            $html = view('components.setting-field', [
                'setting' => $setting,
            ])->render();

            $this->assertSame('', trim($html));
        }
    }

    public function test_number_setting_field_renders_when_data_is_null(): void
    {
        $setting = Setting::factory()->number()->create([
            'key' => 'hours_without_limits',
            'title' => 'Hours without limits',
            'value' => '3',
            'data' => null,
        ]);

        $html = view('components.setting-field', [
            'setting' => $setting,
        ])->render();

        $this->assertStringContainsString('<increment', $html);
        $this->assertStringContainsString('xname="hours_without_limits"', $html);
    }

    public function test_number_setting_field_renders_data_attributes(): void
    {
        $setting = Setting::factory()->number()->create([
            'key' => 'hours_with_limits',
            'value' => '3',
            'data' => json_encode(['xmin' => 1, 'xmax' => 24]),
        ]);

        $html = view('components.setting-field', [
            'setting' => $setting,
        ])->render();

        $this->assertStringContainsString('xmin="1"', $html);
        $this->assertStringContainsString('xmax="24"', $html);
    }

    public function test_home_featured_tags_setting_is_registered_and_renders_a_tag_multi_select(): void
    {
        $setting = Setting::where('key', FeaturedProductsService::SETTING_KEY)->first();

        $this->assertNotNull($setting, 'The featured products setting is missing.');
        $this->assertSame('TAG_SET', $setting->type);
        $this->assertSame('Homepage', $setting->section);

        $tag = Tag::findOrCreate('ویژه', FeaturedProductsService::TAG_TYPE);
        $ids = json_encode([$tag->id]);
        $setting->update(['value' => $ids, 'raw' => $ids]);

        $this->withViewErrors([]);

        $html = view('components.setting-field', [
            'setting' => $setting->fresh(),
            'tags' => [['id' => $tag->id, 'name' => $tag->name]],
        ])->render();

        $this->assertStringContainsString('<searchable-multi-select', $html);
        $this->assertStringContainsString('xname="'.FeaturedProductsService::SETTING_KEY.'"', $html);
        $this->assertStringContainsString('"id":'.$tag->id, $html);
    }

    public function test_settings_page_has_a_home_page_tab_holding_the_tag_picker(): void
    {
        Role::findOrCreate('admin', 'web');
        $user = User::factory()->create(['role' => 'ADMIN']);
        $user->assignRole('admin');

        Tag::findOrCreate('ویژه', FeaturedProductsService::TAG_TYPE);

        $response = $this->actingAs($user)->get(route('admin.setting.index'));

        $response->assertOk();
        $response->assertSee('id="tab-homepage"', false);
        $response->assertSee('xname="'.FeaturedProductsService::SETTING_KEY.'"', false);
        // The card must not clip the absolutely positioned tag dropdown.
        $response->assertSee('item-list overflow-visible', false);
    }
}
