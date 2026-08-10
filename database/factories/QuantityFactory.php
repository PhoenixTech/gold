<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quantity>
 */
class QuantityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $weight = $this->faker->randomFloat(3, 1, 20);

        return [
            'product_id' => Product::factory(),
            'count' => 1,
            'price' => 0,
            'weight' => $weight,
            'code' => strtoupper($this->faker->bothify('??###')),
            'data' => json_encode(['weight' => $weight]),
            'image' => null,
        ];
    }

    public function sold(): static
    {
        return $this->state(fn () => ['count' => 0]);
    }
}
