<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\LoyaltyOffer;
use App\Models\LoyaltyProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyProgress>
 */
class LoyaltyProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'loyalty_offer_id' => LoyaltyOffer::factory(),
            'progress_count' => $this->faker->numberBetween(0, 3),
        ];
    }
}
