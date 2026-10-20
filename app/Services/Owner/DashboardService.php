<?php

namespace App\Services\Owner;

use App\Enums\OrderStatus;
use App\Enums\ProductStatus;
use App\Http\Resources\OrderResource;
use App\Http\Resources\SubscriptionResource;
use App\Models\Restaurant;
use App\Repositories\CategoryRepository;
use App\Repositories\OrderRepository;
use App\Repositories\ProductRepository;
use App\Repositories\QrScanRepository;
use App\Repositories\RestaurantTableRepository;
use App\Services\SubscriptionService;
use Carbon\CarbonInterface;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DashboardService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private RestaurantTableRepository $tableRepository,
        private QrScanRepository $qrScanRepository,
        private QrAnalyticsService $qrAnalyticsService,
        private CustomerService $customerService,
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
            'qr_scans' => $this->qrScansCard($restaurantId, $from, $to),
            'total_customers' => $this->customerService->countForRestaurant($restaurantId),
            'subscription' => $subscription ? new SubscriptionResource($subscription) : null,
            'monthly_stats' => $this->monthlyStats($restaurantId),
            'recent_orders' => $this->recentOrders($restaurantId),
        ];
    }

    /**
     * Total scans plus a per-table breakdown — "table 1: 10 scans, table 2:
     * 8 scans" — for the dashboard's QR scan card.
     *
     * @return array<string, mixed>
     */
    private function qrScansCard(int $restaurantId, ?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $analytics = $this->qrAnalyticsService->getAnalytics($restaurantId, $from, $to);

        return [
            'total' => $analytics['total_scans'],
            'by_table' => $analytics['tables'],
        ];
    }

    /**
     * Last 12 months of orders, revenue, QR scans, and distinct customers
     * for this restaurant, for the dashboard's month-wise graph — always a
     * fixed trailing window, independent of any $from/$to filter applied to
     * the other figures.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlyStats(int $restaurantId): array
    {
        $months = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();

            $months[] = [
                'year' => $monthStart->year,
                'month' => $monthStart->month,
                'label' => $monthStart->format('M Y'),
                'orders' => $this->orderRepository->countForRestaurant($restaurantId, $monthStart, $monthEnd),
                'revenue' => $this->orderRepository->sumRevenueForRestaurant($restaurantId, $monthStart, $monthEnd),
                'scans' => $this->qrScanRepository->countForRestaurant($restaurantId, $monthStart, $monthEnd),
                'customers' => $this->customerService->countForRestaurant($restaurantId, $monthStart, $monthEnd),
            ];
        }

        return $months;
    }

    private function recentOrders(int $restaurantId): AnonymousResourceCollection
    {
        return OrderResource::collection($this->orderRepository->getRecentForRestaurant($restaurantId, 5));
    }
}
