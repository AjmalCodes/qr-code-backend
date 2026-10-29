<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\RestaurantStatus;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Owner\RegisterRequest;
use App\Http\Resources\Owner\OwnerProfileResource;
use App\Services\AuthService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(
        private AuthService $authService,
        private RestaurantService $restaurantService,
    ) {}

    /**
     * Self-registration for a new restaurant owner. The restaurant starts
     * "pending" — it cannot log in until Super Admin reviews and approves
     * it (see RestaurantService::registerRestaurant()).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $restaurant = $this->restaurantService->registerRestaurant($request->validated());

        return $this->success([
            'id' => $restaurant->id,
            'name' => $restaurant->name,
            'email' => $restaurant->email,
            'status' => $restaurant->status->value,
        ], 'Registration received. Your account is pending review by our team — you\'ll be able to log in once approved.', 201);
    }

    /**
     * Authenticate a restaurant owner and issue an API token.
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

        if (! $user->isRestaurant()) {
            return $this->error('This account is not a restaurant owner account.', null, 403);
        }

        if ($user->restaurant?->status === RestaurantStatus::Pending) {
            return $this->error('Your restaurant registration is still pending review by our team.', null, 403);
        }

        if (! $user->restaurant?->isActive()) {
            return $this->error('This restaurant has been disabled by the platform administrator.', null, 403);
        }

        $token = $user->createToken('owner-api-token')->plainTextToken;

        return $this->success([
            'user' => new OwnerProfileResource($user->load('restaurant')),
            'token' => $token,
        ], 'Login successful');
    }

    /**
     * Revoke the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }
}
