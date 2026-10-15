<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Restaurant;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'plan_name' => $this->faker->randomElement(['Basic', 'Pro', 'Enterprise']),
            'amount' => $this->faker->randomElement([9.99, 29.99, 99.99]),
            'status' => SubscriptionStatus::Active->value,
            'starts_at' => now()->subDays(5),
            'ends_at' => now()->addMonth(),
            'cancelled_at' => null,
        ];
    }

    public function trial(): static
    {
        return $this->state(fn () => ['status' => SubscriptionStatus::Trial->value]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Expired->value,
            'ends_at' => now()->subDay(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }
}
