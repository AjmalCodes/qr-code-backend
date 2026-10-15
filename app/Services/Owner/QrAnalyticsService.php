<?php

namespace App\Services\Owner;

use App\Models\RestaurantTable;
use App\Repositories\OrderRepository;
use App\Repositories\QrScanRepository;
use App\Repositories\RestaurantTableRepository;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class QrAnalyticsService
{
    public function __construct(
        private QrScanRepository $qrScanRepository,
        private OrderRepository $orderRepository,
        private RestaurantTableRepository $tableRepository,
    ) {}

    /**
     * Record a scan for a table. Called from the public menu endpoint when
     * it's loaded with a ?table= token — every page load counts as one scan.
     */
    public function logScan(RestaurantTable $table, Request $request): void
    {
        $this->qrScanRepository->create([
            'restaurant_id' => $table->restaurant_id,
            'restaurant_table_id' => $table->id,
            'ip_address' => $request->ip(),
        ]);
    }

    /**
     * Per-table scan and order counts, plus restaurant-wide totals and
     * scan-to-order conversion rate, for the Owner Dashboard's QR analytics.
     *
     * @return array<string, mixed>
     */
    public function getAnalytics(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $scansByTable = $this->qrScanRepository->countsByTableForRestaurant($restaurantId, $from, $to)
            ->keyBy('restaurant_table_id');
        $ordersByTable = $this->orderRepository->countsByTableForRestaurant($restaurantId, $from, $to)
            ->keyBy('restaurant_table_id');

        $tables = $this->tableRepository->getAllForRestaurant($restaurantId);

        $perTable = $tables->map(function (RestaurantTable $table) use ($scansByTable, $ordersByTable) {
            $scans = (int) ($scansByTable->get($table->id)?->scans ?? 0);
            $orders = (int) ($ordersByTable->get($table->id)?->orders ?? 0);

            return [
                'table_id' => $table->id,
                'label' => $table->label,
                'scans' => $scans,
                'orders' => $orders,
                'conversion_rate' => $scans > 0 ? round(($orders / $scans) * 100, 1) : 0.0,
            ];
        });

        $totalScans = (int) $perTable->sum('scans');
        $totalOrders = (int) $perTable->sum('orders');

        return [
            'total_scans' => $totalScans,
            'total_orders' => $totalOrders,
            'conversion_rate' => $totalScans > 0 ? round(($totalOrders / $totalScans) * 100, 1) : 0.0,
            'tables' => $perTable->values(),
        ];
    }
}
