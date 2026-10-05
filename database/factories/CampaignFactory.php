<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Campaign '.$this->faker->unique()->firstNameFemale;

        return [
            'name' => $name,
            'slug' => sluger($name),
            'subtitle' => null,
            'description' => null,
            'badge_text' => null,
            'status' => CampaignStatus::Published,
            'priority' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'occasions' => [],
            'metal_scope' => ['gold', 'silver'],
            'limit' => 6,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => CampaignStatus::Draft]);
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => CampaignStatus::Disabled]);
    }

    public function scheduled(?\DateTimeInterface $startsAt = null): static
    {
        return $this->state(fn () => [
            'starts_at' => $startsAt ?? now()->addWeek(),
            'ends_at' => null,
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn () => [
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
    }

    public function endless(): static
    {
        return $this->state(fn () => ['starts_at' => null, 'ends_at' => null]);
    }

    public function goldOnly(): static
    {
        return $this->state(fn () => ['metal_scope' => ['gold']]);
    }

    public function occasions(array $occasions): static
    {
        return $this->state(fn () => ['occasions' => $occasions]);
    }

    public function priority(int $priority): static
    {
        return $this->state(fn () => ['priority' => $priority]);
    }
}
