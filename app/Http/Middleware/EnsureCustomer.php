<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the bearer token belongs to a Customer. Sanctum resolves tokens
 * polymorphically by their tokenable_type regardless of guard name, so a
 * plain `auth:customer` guard check would also accept a staff/admin token —
 * this middleware explicitly verifies the resolved model's class.
 */
class EnsureCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
                'errors' => null,
            ], 401);
        }

        return $next($request);
    }
}
