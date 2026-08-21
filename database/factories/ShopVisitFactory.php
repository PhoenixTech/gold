<?php

namespace Database\Factories;

use App\Enums\ShopVisitStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ShopVisit>
 */
class ShopVisitFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->visitor(),
            'status' => ShopVisitStatus::Completed,
            'mobile' => '0912'.$this->faker->numerify('#######'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'has_purchase' => true,
            'has_own_workshop' => false,
            'other_reason' => null,
            'categories' => ['gold'],
            'work_styles' => ['minimal'],
            'mall' => 'بازار بزرگ تهران',
            'address' => 'پلاک ۱۲',
            'submitted_at' => now(),
        ];
    }

    public function collecting(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShopVisitStatus::Collecting,
            'mobile' => null,
            'first_name' => null,
            'last_name' => null,
            'has_purchase' => null,
            'categories' => null,
            'work_styles' => null,
            'mall' => null,
            'address' => null,
            'submitted_at' => null,
        ]);
    }

    public function awaitingAddress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShopVisitStatus::StepTwo,
            'categories' => null,
            'work_styles' => null,
            'mall' => null,
            'address' => null,
            'submitted_at' => null,
        ]);
    }
}
