<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Models\Restaurant;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    use ApiResponses;

    public function __construct(private SubscriptionService $subscriptionService) {}

    /**
     * The authenticated owner's current (most recent) subscription, read-only.
     */
    public function show(Request $request): JsonResponse
    {
        $restaurant = $request->attributes->get('tenant_restaurant');
        /** @var Restaurant $restaurant */
        $subscription = $this->subscriptionService->findLatestForRestaurant($restaurant->id);

        if (! $subscription) {
            return $this->error('No subscription found for this restaurant.', null, 404);
        }

        return $this->success(new SubscriptionResource($subscription->load('restaurant')));
    }
}
