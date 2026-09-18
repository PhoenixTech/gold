<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => sluger($name).'-'.$this->faker->unique()->numerify('###'),
            'subtitle' => null,
            'description' => null,
            'sort' => 0,
            'hide' => false,
        ];
    }
}
