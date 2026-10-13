<?php

namespace App\Services\Owner;

use App\Models\LoyaltyOffer;
use App\Repositories\LoyaltyOfferRepository;
use Illuminate\Database\Eloquent\Collection;

class LoyaltyOfferService
{
    public function __construct(private LoyaltyOfferRepository $loyaltyOfferRepository) {}

    public function list(int $restaurantId): Collection
    {
        return $this->loyaltyOfferRepository->getAllForRestaurant($restaurantId);
    }

    public function find(int $id, int $restaurantId): ?LoyaltyOffer
    {
        return $this->loyaltyOfferRepository->findByIdForRestaurant($id, $restaurantId);
    }

    public function create(int $restaurantId, array $data): LoyaltyOffer
    {
        $data['restaurant_id'] = $restaurantId;
        $data['is_active'] ??= true;

        return $this->loyaltyOfferRepository->create($data);
    }

    public function update(LoyaltyOffer $offer, array $data): LoyaltyOffer
    {
        $this->loyaltyOfferRepository->update($offer, $data);

        return $offer->fresh();
    }

    public function delete(LoyaltyOffer $offer): bool
    {
        return $this->loyaltyOfferRepository->delete($offer);
    }

    public function toggleActive(LoyaltyOffer $offer): LoyaltyOffer
    {
        $this->loyaltyOfferRepository->update($offer, ['is_active' => ! $offer->is_active]);

        return $offer->fresh();
    }
}
