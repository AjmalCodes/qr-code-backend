<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RestaurantController extends Controller
{
    public function __construct(private RestaurantService $restaurantService) {}

    /**
     * List restaurants - Super admin only. Supports ?search=, ?status=, ?per_page=.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $restaurants = $this->restaurantService->search($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => collect($restaurants->items())->map(fn (Restaurant $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'email' => $r->email,
                'phone' => $r->phone,
                'address' => $r->address,
                'status' => $r->status->value,
                'created_at' => $r->created_at,
            ]),
            'meta' => [
                'current_page' => $restaurants->currentPage(),
                'last_page' => $restaurants->lastPage(),
                'per_page' => $restaurants->perPage(),
                'total' => $restaurants->total(),
            ],
        ]);
    }

    /**
     * Create a new restaurant - Super admin only
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:restaurants,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $restaurant = $this->restaurantService->createRestaurant($validated);

            return response()->json([
                'message' => 'Restaurant created successfully',
                'data' => [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->slug,
                    'email' => $restaurant->email,
                    'phone' => $restaurant->phone,
                    'address' => $restaurant->address,
                    'status' => $restaurant->status->value,
                    'created_at' => $restaurant->created_at,
                ],
            ], 201);
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get a specific restaurant - Super admin only
     */
    public function show(Restaurant $restaurant): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'email' => $restaurant->email,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'status' => $restaurant->status->value,
                'user_id' => $restaurant->user_id,
                'created_at' => $restaurant->created_at,
                'updated_at' => $restaurant->updated_at,
            ],
        ]);
    }

    /**
     * Update a restaurant - Super admin only
     */
    public function update(Request $request, Restaurant $restaurant): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $updated = $this->restaurantService->updateRestaurant($restaurant, $validated);

        return response()->json([
            'message' => 'Restaurant updated successfully',
            'data' => [
                'id' => $updated->id,
                'name' => $updated->name,
                'slug' => $updated->slug,
                'email' => $updated->email,
                'phone' => $updated->phone,
                'address' => $updated->address,
                'status' => $updated->status->value,
                'updated_at' => $updated->updated_at,
            ],
        ]);
    }

    /**
     * Delete a restaurant - Super admin only
     */
    public function destroy(Restaurant $restaurant): JsonResponse
    {
        $this->restaurantService->deleteRestaurant($restaurant);

        return response()->json([
            'message' => 'Restaurant deleted successfully',
        ]);
    }

    /**
     * Instantly enable or disable a restaurant - Super admin only.
     * Every tenant-scoped request (owner login, staff login, public menu)
     * re-checks this status, so access is blocked on the very next request.
     * Returns the full restaurant plus its owner's user profile.
     */
    public function toggleStatus(Restaurant $restaurant): JsonResponse
    {
        $restaurant = $this->restaurantService->toggleStatus($restaurant);

        return response()->json([
            'message' => 'Restaurant status updated successfully',
            'data' => [
                'id' => $restaurant->id,
                'name' => $restaurant->name,
                'slug' => $restaurant->slug,
                'email' => $restaurant->email,
                'phone' => $restaurant->phone,
                'address' => $restaurant->address,
                'description' => $restaurant->description,
                'currency' => $restaurant->currency,
                'logo_url' => $restaurant->logo_url,
                'status' => $restaurant->status->value,
                'user_id' => $restaurant->user_id,
                'created_at' => $restaurant->created_at,
                'updated_at' => $restaurant->updated_at,
                'user' => $restaurant->user ? [
                    'id' => $restaurant->user->id,
                    'name' => $restaurant->user->name,
                    'email' => $restaurant->user->email,
                    'phone' => $restaurant->user->phone,
                    'role' => $restaurant->user->role->value,
                    'created_at' => $restaurant->user->created_at,
                ] : null,
            ],
        ]);
    }
}
