<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService) {}

    /**
     * List subscriptions - Super admin only. Supports ?status=, ?search=, ?per_page=.
     * Pass ?status=active to see exactly the restaurants currently on a paid/active plan.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string', 'in:trial,active,expired,cancelled'],
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $subscriptions = $this->subscriptionService->search($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions->items()),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'last_page' => $subscriptions->lastPage(),
                'per_page' => $subscriptions->perPage(),
                'total' => $subscriptions->total(),
            ],
        ]);
    }

    /**
     * Create/assign a subscription to a restaurant - Super admin only.
     */
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return response()->json([
            'message' => 'Subscription created successfully',
            'data' => new SubscriptionResource($subscription->load('restaurant')),
        ], 201);
    }

    public function show(Subscription $subscription): JsonResponse
    {
        return response()->json([
            'data' => new SubscriptionResource($subscription->load('restaurant')),
        ]);
    }

    public function update(UpdateSubscriptionRequest $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->update($subscription, $request->validated());

        return response()->json([
            'message' => 'Subscription updated successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Cancel a subscription (keeps the record for billing history).
     */
    public function cancel(Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->cancel($subscription);

        return response()->json([
            'message' => 'Subscription cancelled successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    /**
     * Permanently delete a subscription record - Super admin only.
     * Prefer "cancel" for the normal business action; this is for
     * correcting mistakes (e.g. a subscription created in error).
     */
    public function destroy(Subscription $subscription): JsonResponse
    {
        $subscription->delete();

        return response()->json([
            'message' => 'Subscription deleted successfully',
        ]);
    }
}
