<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user is a restaurant owner and binds their
 * restaurant onto the request for multi-tenant scoping downstream.
 */
class EnsureRestaurantOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sanctum resolves tokens polymorphically regardless of guard name,
        // so a Customer's token could otherwise reach here too — confirm
        // the resolved model is actually a User before checking its role.
        if (! $user instanceof User || ! $user->isRestaurant()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Restaurant owner access required.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        $restaurant = $user->restaurant;

        if (! $restaurant) {
            return response()->json([
                'success' => false,
                'message' => 'No restaurant is associated with this account.',
                'data' => null,
                'errors' => null,
            ], 404);
        }

        if (! $restaurant->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'This restaurant has been disabled by the platform administrator.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        // Bind the tenant restaurant onto the request so controllers/services
        // never need to re-derive it, keeping every query scoped by default.
        $request->attributes->set('tenant_restaurant', $restaurant);

        return $next($request);
    }
}
