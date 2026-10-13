<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\RestaurantResource;
use App\Services\PublicMenuService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    use ApiResponses;

    public function __construct(
        private RestaurantService $restaurantService,
        private PublicMenuService $publicMenuService,
    ) {}

    /**
     * Public, unauthenticated menu for a restaurant. Only an active
     * restaurant's active categories and active products are returned —
     * out-of-stock/inactive items are excluded, not merely flagged.
     */
    public function show(string $slug): JsonResponse
    {
        $restaurant = $this->restaurantService->findActiveBySlug($slug);

        if (! $restaurant) {
            return $this->error('Restaurant not found.', null, 404);
        }

        $categories = $this->publicMenuService->getMenu($restaurant);

        return $this->success([
            'restaurant' => new RestaurantResource($restaurant),
            'categories' => CategoryResource::collection($categories),
        ]);
    }
}
