<?php

namespace App\Services;

use App\Enums\LoyaltyClaimStatus;
use App\Models\Customer;
use App\Models\LoyaltyClaim;
use App\Models\LoyaltyOffer;
use App\Models\Restaurant;
use App\Repositories\LoyaltyClaimRepository;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoyaltyService
{
    public function __construct(private LoyaltyClaimRepository $loyaltyClaimRepository) {}

    /**
     * A verified customer claims an active offer, generating a one-time
     * redemption code that staff must confirm in person (Waiter Panel).
     */
    public function claim(Customer $customer, LoyaltyOffer $offer): LoyaltyClaim
    {
        if (! $offer->is_active) {
            throw ValidationException::withMessages([
                'offer' => ['This offer is no longer active.'],
            ]);
        }

        return $this->loyaltyClaimRepository->create([
            'loyalty_offer_id' => $offer->id,
            'customer_id' => $customer->id,
            'restaurant_id' => $offer->restaurant_id,
            'redemption_code' => $this->uniqueCode(),
            'status' => LoyaltyClaimStatus::Pending->value,
        ]);
    }

    /**
     * Staff confirms a customer's redemption code at the table, closing the
     * verification loop required before the reward is honored.
     *
     * @throws ValidationException when the code is unknown or already resolved
     */
    public function verify(Restaurant $restaurant, string $code, int $staffUserId): LoyaltyClaim
    {
        $claim = $this->loyaltyClaimRepository->findByCodeForRestaurant($code, $restaurant->id);

        if (! $claim) {
            throw ValidationException::withMessages([
                'code' => ['This redemption code was not found for this restaurant.'],
            ]);
        }

        if ($claim->status !== LoyaltyClaimStatus::Pending) {
            throw ValidationException::withMessages([
                'code' => ["This code has already been {$claim->status->value}."],
            ]);
        }

        $this->loyaltyClaimRepository->update($claim, [
            'status' => LoyaltyClaimStatus::Verified->value,
            'verified_by_user_id' => $staffUserId,
            'verified_at' => now(),
        ]);

        return $claim->fresh(['offer', 'customer']);
    }

    private function uniqueCode(): string
    {
        do {
            // Uppercase alphanumeric, easy to read aloud and type at the table.
            $code = Str::upper(Str::random(8));
        } while ($this->loyaltyClaimRepository->codeExists($code));

        return $code;
    }
}
