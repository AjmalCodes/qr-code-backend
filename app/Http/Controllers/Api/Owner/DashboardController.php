<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\Owner\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ApiResponses;

    public function __construct(private DashboardService $dashboardService) {}

    /**
     * All-time + today snapshot for the dashboard landing screen: order/
     * revenue/menu/table cards, QR scans (total and per-table), total
     * customers, current subscription, a 12-month graph, and recent orders.
     */
    public function stats(Request $request): JsonResponse
    {
        return $this->success($this->dashboardService->getStats($this->tenant($request)));
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
