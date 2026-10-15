<?php

namespace App\Services\Owner;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Resources\SubscriptionResource;
use App\Models\Restaurant;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\QrScanRepository;
use App\Repositories\RestaurantTableRepository;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;

class DashboardService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private RestaurantTableRepository $tableRepository,
        private QrScanRepository $qrScanRepository,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Everything the Owner Dashboard's landing screen needs in one call.
     * Pass $from/$to to scope the order/revenue/scan figures to a date
     * range instead of all-time — the Reports endpoint uses this directly,
     * so a report is always built from exactly the same data as the dashboard.
     *
     * @return array<string, mixed>
     */
    public function getStats(Restaurant $restaurant, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $restaurantId = $restaurant->id;
        $subscription = $this->subscriptionService->findLatestForRestaurant($restaurantId);
        $isDateRanged = $from !== null || $to !== null;

        return [
            'period' => $isDateRanged ? ['from' => $from?->toDateString(), 'to' => $to?->toDateString()] : null,
            'orders' => [
                'total' => $this->orderRepository->countForRestaurant($restaurantId, $from, $to),
                'today' => $isDateRanged ? null : $this->orderRepository->countForRestaurant($restaurantId, today()),
                'pending' => $this->orderRepository->countForRestaurantByStatus($restaurantId, OrderStatus::Pending->value, $from, $to),
                'preparing' => $this->orderRepository->countForRestaurantByStatus($restaurantId, OrderStatus::Preparing->value, $from, $to),
                'completed' => $this->orderRepository->countForRestaurantByStatus($restaurantId, OrderStatus::Completed->value, $from, $to),
            ],
            'revenue' => [
                'total' => $this->orderRepository->sumRevenueForRestaurant($restaurantId, $from, $to),
                'today' => $isDateRanged ? null : $this->orderRepository->sumRevenueForRestaurant($restaurantId, today()),
            ],
            'menu' => [
                'categories' => $this->categoryRepository->countForRestaurant($restaurantId),
                'products' => $this->productRepository->countForRestaurant($restaurantId),
                'active_products' => $this->productRepository->countForRestaurantByStatus($restaurantId, ProductStatus::Active->value),
                'out_of_stock_products' => $this->productRepository->countForRestaurantByStatus($restaurantId, ProductStatus::OutOfStock->value),
            ],
            'tables' => $this->tableRepository->countForRestaurant($restaurantId),
            'qr_scans_total' => $this->qrScanRepository->countForRestaurant($restaurantId, $from, $to),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
        ];
    }
}
