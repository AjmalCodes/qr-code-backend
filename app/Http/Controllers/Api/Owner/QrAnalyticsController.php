<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\Owner\QrAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QrAnalyticsController extends Controller
{
    use ApiResponses;

    public function __construct(private QrAnalyticsService $qrAnalyticsService) {}

    /**
     * Per-table QR scan and order counts, with conversion rate, plus
     * restaurant-wide totals. Optionally scope to ?from=&to= (Y-m-d).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : null;

        $analytics = $this->qrAnalyticsService->getAnalytics($this->tenant($request)->id, $from, $to);

        return $this->success($analytics);
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
