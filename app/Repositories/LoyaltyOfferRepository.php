<?php

namespace App\Repositories;

use App\Models\LoyaltyOffer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function getAllForRestaurant(int $restaurantId, int $perPage = 15): LengthAwarePaginator
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)->latest()->paginate($perPage);
    }

    public function getActiveForRestaurant(int $restaurantId, int $perPage = 15): LengthAwarePaginator
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)->where('is_active', true)->latest()->paginate($perPage);
    }

    /**
     * Active offers that a purchase of this product counts toward — either
     * tied to this exact product, or a restaurant-wide offer (product_id
     * is null, meaning "any product").
     */
    public function getActiveForRestaurantAndProduct(int $restaurantId, int $productId): Collection
    {
        return LoyaltyOffer::where('restaurant_id', $restaurantId)
            ->where('is_active', true)
            ->where(fn ($query) => $query->where('product_id', $productId)->orWhereNull('product_id'))
            ->get();
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
