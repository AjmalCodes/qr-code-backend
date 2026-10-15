<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\ChangePasswordRequest;
use App\Http\Requests\Owner\UpdateProfileRequest;
use App\Http\Resources\Owner\OwnerProfileResource;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponses;

    public function __construct(private ProfileService $profileService) {}

    /**
     * Fetch the current logged-in owner and their restaurant details.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('restaurant');

        return $this->success(new OwnerProfileResource($user));
    }

    /**
     * Update the owner's own account details (name, email, phone, avatar).
     * Send as multipart/form-data (with `_method=PUT`) to upload a photo.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile(
            $request->user(),
            $request->safe()->except('avatar'),
            $request->file('avatar'),
        );

        return $this->success(new OwnerProfileResource($user->load('restaurant')), 'Profile updated successfully');
    }

    /**
     * Change the owner's password after validating the current one.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->profileService->changePassword($request->user(), $request->validated('password'));

        return $this->success(null, 'Password changed successfully');
    }
}
