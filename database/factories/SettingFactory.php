<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $value = fake()->word();

        return [
            'section' => 'General',
            'type' => 'TEXT',
            'title' => fake()->words(3, true),
            'active' => true,
            'key' => 'setting_'.Str::random(8),
            'value' => $value,
            'raw' => $value,
            'ltr' => false,
            'is_basic' => false,
            'size' => 12,
            'data' => null,
        ];
    }

    public function number(): static
    {
        return $this->state(function (array $attributes): array {
            $value = (string) fake()->numberBetween(1, 24);

            return [
                'type' => 'NUMBER',
                'ltr' => true,
                'value' => $value,
                'raw' => $value,
            ];
        });
    }
}
