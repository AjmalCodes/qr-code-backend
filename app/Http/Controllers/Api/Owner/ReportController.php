<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\Owner\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{
    use ApiResponses;

    public function __construct(private DashboardService $dashboardService) {}

    /**
     * The same metrics shown on the dashboard, scoped to a date range via
     * ?from=&to= (Y-m-d, both optional — omit both for an all-time report).
     * Built from the exact same data source as GET /owner/dashboard/stats,
     * so a report always matches what the dashboard shows for that period.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;

        $report = $this->dashboardService->getStats($this->tenant($request), $from, $to);

        return $this->success($report);
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
