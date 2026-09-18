<?php

namespace Database\Factories;

use App\Helpers\PersianFaker;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'mobile' => PersianFaker::mobile(),
            'email' => $this->faker->unique()->email,
            'password' => bcrypt('password'),
            'credit' => 0,
            'description' => __('Credit card:').PHP_EOL.PersianFaker::shetabCard(),
        ];
    }
}
