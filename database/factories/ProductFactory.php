<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = 'Ring '.$this->faker->unique()->firstNameFemale;

        return [
            'name' => $title,
            'slug' => sluger($title),
            'excerpt' => $this->faker->realText(150),
            'user_id' => \App\Models\User::factory(),
            'category_id' => \App\Models\Category::factory(),
            'description' => $this->faker->realText(600),
            'stock_quantity' => 0,
            'stock_status' => 'IN_STOCK',
            'status' => 1,
            'price' => 0,
            'buy_price' => 0,
            'sku' => $this->faker->unique()->ean8(),
            'metal_type' => 'gold',
            'target_group' => 'unisex',
            'labor_charge_1' => 15,
            'wage' => 15,
            'profit' => 7,
            'tax' => 9,
            'addon' => 0,
        ];
    }
}
