<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RestaurantStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Repositories\RestaurantRepository;

class AdminDashboardService
{
    public function __construct(
        private RestaurantRepository $restaurantRepository,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Platform-wide metrics for the Super Admin dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'total_restaurants' => $this->restaurantRepository->count(),
            'active_restaurants' => $this->restaurantRepository->countByStatus(RestaurantStatus::Active->value),
            'inactive_restaurants' => $this->restaurantRepository->countByStatus(RestaurantStatus::Inactive->value),
            'total_orders' => Order::count(),
            'orders_today' => Order::whereDate('created_at', today())->count(),
            'pending_orders' => Order::where('status', OrderStatus::Pending->value)->count(),
            'active_subscriptions' => $this->subscriptionService->countActive(),
            'recent_restaurants' => $this->restaurantRepository->recent(5)->map(fn (Restaurant $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'status' => $r->status->value,
                'created_at' => $r->created_at,
            ]),
        ];
    }
}
