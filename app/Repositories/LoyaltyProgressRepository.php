<?php

namespace App\Repositories;

use App\Models\LoyaltyProgress;
use Illuminate\Database\Eloquent\Collection;

class LoyaltyProgressRepository
{
    public function findForCustomerAndOffer(int $customerId, int $offerId): ?LoyaltyProgress
    {
        return LoyaltyProgress::where('customer_id', $customerId)
            ->where('loyalty_offer_id', $offerId)
            ->first();
    }

    /**
     * All progress rows for a customer, keyed by offer id, for pairing
     * against the restaurant's active offers in one query.
     *
     * @return Collection<int, LoyaltyProgress>
     */
    public function getForCustomer(int $customerId): Collection
    {
        return LoyaltyProgress::where('customer_id', $customerId)->get()->keyBy('loyalty_offer_id');
    }

    /**
     * Add to a customer's punch-card count for an offer — called every time
     * they order a qualifying product. Creates the row on first purchase.
     */
    public function incrementProgress(int $customerId, int $offerId, int $by): LoyaltyProgress
    {
        $progress = LoyaltyProgress::firstOrCreate(
            ['customer_id' => $customerId, 'loyalty_offer_id' => $offerId],
            ['progress_count' => 0],
        );

        $progress->increment('progress_count', $by);

        return $progress->fresh();
    }

    /**
     * Deduct the cost of a redeemed reward from the punch-card count —
     * never below zero, so any surplus purchases still count forward.
     */
    public function deduct(int $customerId, int $offerId, int $by): void
    {
        $progress = $this->findForCustomerAndOffer($customerId, $offerId);

        if (! $progress) {
            return;
        }

        $progress->update(['progress_count' => max(0, $progress->progress_count - $by)]);
    }
}
