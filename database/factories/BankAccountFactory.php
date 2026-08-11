<?php

namespace Database\Factories;

use App\Models\BankAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BankAccount>
 */
class BankAccountFactory extends Factory
{
    protected $model = BankAccount::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_number' => '6037'.fake()->numerify('############'),
            'account_number' => fake()->numerify('##########'),
            'iban' => 'IR'.fake()->numerify('########################'),
            'bank_name' => fake()->randomElement(['Melli', 'Mellat', 'Saderat', 'Pasargad']),
            'account_holder_name' => fake()->name(),
            'is_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }
}
