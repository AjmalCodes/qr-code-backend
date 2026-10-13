<?php

namespace App\Repositories;

use App\Models\LoyaltyOffer;
use Illuminate\Database\Eloquent\Collection;

class LoyaltyOfferRepository
{
    public function create(array $data): LoyaltyOffer
    {
        return LoyaltyOffer::create($data);
    }

    public function findByIdForRestaurant(int $id, int $restaurantId): ?LoyaltyOffer
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)->find($id);
    }

    public function getAllForRestaurant(int $restaurantId): Collection
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)->latest()->get();
    }

    public function getActiveForRestaurant(int $restaurantId): Collection
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)->where('is_active', true)->latest()->get();
    }

    public function update(LoyaltyOffer $offer, array $data): bool
    {
        return $offer->update($data);
    }

    public function delete(LoyaltyOffer $offer): bool
    {
        return $offer->delete();
    }
}
