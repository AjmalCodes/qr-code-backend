<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends Controller
{
    public function __construct(private AdminDashboardService $adminDashboardService) {}

    /**
     * Platform-wide metrics: total restaurants, active outlets, order activity.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => $this->adminDashboardService->getStats(),
        ]);
    }
}
