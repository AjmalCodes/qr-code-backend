<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\RestaurantResource;
use App\Repositories\RestaurantTableRepository;
use App\Services\Owner\QrAnalyticsService;
use App\Services\PublicMenuService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    use ApiResponses;

    public function __construct(
        private RestaurantService $restaurantService,
        private PublicMenuService $publicMenuService,
        private RestaurantTableRepository $tableRepository,
        private QrAnalyticsService $qrAnalyticsService,
    ) {}

    /**
     * Public, unauthenticated menu for a restaurant. Only an active
     * restaurant's active categories and active products are returned —
     * out-of-stock/inactive items are excluded, not merely flagged.
     *
     * Pass ?table={qr_token} (the value encoded in the table's printed QR
     * code) to record a scan for the Owner's QR analytics — every load with
     * a valid table token counts as one scan, separate from orders placed.
     */
    public function show(Request $request, string $slug): JsonResponse
    {
        $restaurant = $this->restaurantService->findActiveBySlug($slug);

        if (! $restaurant) {
            return $this->error('Restaurant not found.', null, 404);
        }

        $tableToken = $request->query('table');

        if ($tableToken) {
            $table = $this->tableRepository->findByQrToken($tableToken);

            if ($table && $table->restaurant_id === $restaurant->id) {
                $this->qrAnalyticsService->logScan($table, $request);
            }
        }

        $categories = $this->publicMenuService->getMenu($restaurant);

        return $this->success([
            'restaurant' => new RestaurantResource($restaurant),
            'categories' => CategoryResource::collection($categories),
        ]);
    }
}
