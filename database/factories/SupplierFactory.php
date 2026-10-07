<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company_name' => fake()->company(),
            'phone' => fake()->phoneNumber(),
            'account_number' => fake()->numerify('############'),
            'iban' => 'IR'.fake()->numerify('########################'),
        ];
    }
}
