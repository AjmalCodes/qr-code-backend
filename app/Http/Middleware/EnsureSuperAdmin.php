<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
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
        if (! $user instanceof User || ! $user->isSuperAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Super admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
