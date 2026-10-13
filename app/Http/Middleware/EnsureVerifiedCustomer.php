<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Closes the loyalty verification loop at the gate: must run after
 * EnsureCustomer. Blocks any customer who has not confirmed their email
 * from claiming or redeeming an offer.
 */
class EnsureVerifiedCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $customer = $request->user('customer');

        if (! $customer instanceof Customer || ! $customer->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before claiming loyalty offers.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
