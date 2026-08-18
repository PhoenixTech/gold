<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
