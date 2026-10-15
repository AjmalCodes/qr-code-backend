<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Repositories\SubscriptionRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class SubscriptionService
{
    public function __construct(private SubscriptionRepository $subscriptionRepository) {}

    /**
     * Defaults a subscription to a 30-day period when no explicit ends_at
     * is given — the platform's standard billing cycle.
     */
    public function create(array $data): Subscription
    {
        $data['status'] ??= SubscriptionStatus::Active->value;
        $data['starts_at'] ??= now();
        $data['ends_at'] ??= Carbon::parse($data['starts_at'])->addDays(30);

        return $this->subscriptionRepository->create($data);
    }

    public function find(int $id): ?Subscription
    {
        return $this->subscriptionRepository->findById($id);
    }

    public function findLatestForRestaurant(int $restaurantId): ?Subscription
    {
        return $this->subscriptionRepository->findLatestForRestaurant($restaurantId);
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $this->subscriptionRepository->update($subscription, $data);

        return $subscription->fresh('restaurant');
    }

    public function cancel(Subscription $subscription): Subscription
    {
        $this->subscriptionRepository->update($subscription, [
            'status' => SubscriptionStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        return $subscription->fresh('restaurant');
    }

    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->subscriptionRepository->search($filters, $perPage);
    }

    public function countActive(): int
    {
        return $this->subscriptionRepository->countByStatus(SubscriptionStatus::Active->value);
    }

    /**
     * Flips every active subscription whose ends_at has passed to expired.
     * Run daily by the scheduler (see app/Console/Commands/ExpireSubscriptions.php).
     *
     * @return int number of subscriptions expired
     */
    public function expireOverdue(): int
    {
        $overdue = $this->subscriptionRepository->getOverdueActive();

        foreach ($overdue as $subscription) {
            $this->subscriptionRepository->update($subscription, ['status' => SubscriptionStatus::Expired->value]);
        }

        return $overdue->count();
    }
}
