<?php

namespace Database\Factories;

use App\Enums\LoyaltyClaimStatus;
use App\Models\Customer;
use App\Models\LoyaltyClaim;
use App\Models\LoyaltyOffer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LoyaltyClaim>
 */
class LoyaltyClaimFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'loyalty_offer_id' => LoyaltyOffer::factory(),
            'customer_id' => Customer::factory(),
            'restaurant_id' => Restaurant::factory(),
            'redemption_code' => Str::upper(Str::random(8)),
            'status' => LoyaltyClaimStatus::Pending->value,
            'verified_by_user_id' => null,
            'verified_at' => null,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => LoyaltyClaimStatus::Verified->value,
            'verified_at' => now(),
        ]);
    }
}
