<?php

namespace App\Repositories;

use App\Models\LoyaltyClaim;

class LoyaltyClaimRepository
{
    public function create(array $data): LoyaltyClaim
    {
        return LoyaltyClaim::create($data);
    }

    public function findByCodeForRestaurant(string $code, int $restaurantId): ?LoyaltyClaim
    {
        return LoyaltyClaim::with(['offer', 'customer'])
            ->where('restaurant_id', $restaurantId)
            ->where('redemption_code', $code)
            ->first();
    }

    public function findByIdForCustomer(int $id, int $customerId): ?LoyaltyClaim
    {
        return LoyaltyClaim::where('customer_id', $customerId)->find($id);
    }

    public function update(LoyaltyClaim $claim, array $data): bool
    {
        return $claim->update($data);
    }

    public function codeExists(string $code): bool
    {
        return LoyaltyClaim::where('redemption_code', $code)->exists();
    }
}
