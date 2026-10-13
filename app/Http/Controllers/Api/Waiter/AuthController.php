<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(private AuthService $authService) {}

    /**
     * Authenticate a waiter (or the restaurant owner acting as staff) and
     * issue an API token scoped to the Waiter Panel.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = $this->authService->login($credentials['email'], $credentials['password']);

        if (! $user) {
            return $this->error('The provided credentials are incorrect.', [
                'email' => ['The provided credentials are incorrect.'],
            ], 422);
        }

        if (! ($user->isWaiter() || $user->isRestaurant())) {
            return $this->error('This account does not have staff access.', null, 403);
        }

        $restaurant = $user->tenantRestaurant();

        if (! $restaurant?->isActive()) {
            return $this->error('This restaurant has been disabled by the platform administrator.', null, 403);
        }

        $token = $user->createToken('waiter-api-token')->plainTextToken;

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'restaurant_id' => $restaurant->id,
            ],
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
