<?php

namespace Database\Factories;

use App\Models\LoyaltyOffer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyOffer>
 */
class LoyaltyOfferFactory extends Factory
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
            'product_id' => null,
            'title' => 'Buy '.$this->faker->numberBetween(2, 5).' get 1 free',
            'description' => $this->faker->optional()->sentence(),
            'buy_quantity' => $this->faker->numberBetween(2, 5),
            'get_quantity' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
