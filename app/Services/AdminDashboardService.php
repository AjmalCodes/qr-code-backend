<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\RestaurantStatus;
use App\Models\Order;
use App\Repositories\RestaurantRepository;

class AdminDashboardService
{
    public function __construct(private RestaurantRepository $restaurantRepository) {}

    /**
     * Platform-wide metrics for the Super Admin dashboard.
     *
     * @return array<string, int>
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
        ];
    }
}
