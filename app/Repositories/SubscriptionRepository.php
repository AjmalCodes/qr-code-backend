<?php

namespace App\Repositories;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SubscriptionRepository
{
    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function findById(int $id): ?Subscription
    {
        return Subscription::with('restaurant')->find($id);
    }

    public function findLatestForRestaurant(int $restaurantId): ?Subscription
    {
        return Subscription::where('restaurant_id', $restaurantId)->latest()->first();
    }

    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    /**
     * @param  array{status?: string, search?: string}  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Subscription::query()
            ->with('restaurant')
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('restaurant', fn ($r) => $r
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                    )
                ),
            )
            ->latest()
            ->paginate($perPage);
    }

    public function countByStatus(string $status): int
    {
        return Subscription::where('status', $status)->count();
    }

    /**
     * Subscriptions still flagged active whose end date has already passed —
     * candidates for the daily auto-expiry job.
     */
    public function getOverdueActive(): Collection
    {
        return Subscription::where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->get();
    }
}
