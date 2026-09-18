<?php

namespace Database\Factories;

use App\Models\Prop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prop>
 */
class PropFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'icon' => 'remixicon-line',
        ];
    }
}
