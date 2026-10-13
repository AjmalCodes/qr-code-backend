<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateRestaurantSettingsRequest;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Services\Owner\RestaurantSettingsService;
use Illuminate\Http\JsonResponse;

class RestaurantSettingsController extends Controller
{
    use ApiResponses;

    public function __construct(private RestaurantSettingsService $restaurantSettingsService) {}

    /**
     * Update the restaurant's name, description, contact details, currency,
     * and logo. Send as multipart/form-data (with `_method=PUT`) to upload a logo.
     */
    public function update(UpdateRestaurantSettingsRequest $request): JsonResponse
    {
        /** @var Restaurant $restaurant */
        $restaurant = $request->attributes->get('tenant_restaurant');

        $restaurant = $this->restaurantSettingsService->updateSettings(
            $restaurant,
            $request->safe()->except('logo'),
            $request->file('logo'),
        );

        return $this->success(new RestaurantResource($restaurant), 'Restaurant settings updated successfully');
    }
}
