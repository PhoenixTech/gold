<?php

namespace Database\Factories;

use App\Enums\DeliveryStatus;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Delivery>
 */
class DeliveryFactory extends Factory
{
    public const TEST_PIN = '4242';

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'courier_id' => User::factory()->courier(),
            'code_hash' => Hash::make(self::TEST_PIN),
            'status' => DeliveryStatus::Pending,
            'failed_attempts' => 0,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DeliveryStatus::Delivered,
            'accepted_at' => now()->subHour(),
            'delivered_at' => now(),
        ]);
    }
}
