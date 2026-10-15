<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\RestaurantUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantUserController extends Controller
{
    public function __construct(private RestaurantUserService $restaurantUserService) {}

    /**
     * List every restaurant-owner account - Super admin only. One row per
     * user: an owner of several restaurants appears once, with all of their
     * restaurants nested under `restaurants`, never repeated per restaurant.
     * Supports ?search= (matches the owner's name/email or any of their
     * restaurants' name/slug/email) and ?per_page=.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'search' => ['sometimes', 'string', 'max:255'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $owners = $this->restaurantUserService->search($filters, $filters['per_page'] ?? 15);

        return response()->json([
            'data' => collect($owners->items())->map(fn (User $owner) => [
                'id' => $owner->id,
                'name' => $owner->name,
                'email' => $owner->email,
                'phone' => $owner->phone,
                'avatar_url' => $owner->avatar_url,
                'created_at' => $owner->created_at,
                'restaurants' => $owner->restaurants->map(fn (Restaurant $r) => [
                    'id' => $r->id,
                    'name' => $r->name,
                    'slug' => $r->slug,
                    'email' => $r->email,
                    'phone' => $r->phone,
                    'address' => $r->address,
                    'status' => $r->status->value,
                    'created_at' => $r->created_at,
                ]),
            ]),
            'meta' => [
                'current_page' => $owners->currentPage(),
                'last_page' => $owners->lastPage(),
                'per_page' => $owners->perPage(),
                'total' => $owners->total(),
            ],
        ]);
    }
}
