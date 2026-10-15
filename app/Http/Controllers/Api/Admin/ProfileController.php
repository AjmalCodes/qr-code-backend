<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponses;

    public function __construct(private ProfileService $profileService) {}

    /**
     * The authenticated super admin's own account details.
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success($this->present($request->user()));
    }

    /**
     * Update the admin's own account details (name, email, phone, avatar).
     * Send as multipart/form-data (with `_method=PUT`) to upload an avatar.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profileService->updateProfile(
            $request->user(),
            $request->safe()->except('avatar'),
            $request->file('avatar'),
        );

        return $this->success($this->present($user), 'Profile updated successfully');
    }

    /**
     * Change the admin's password after validating the current one.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->profileService->changePassword($request->user(), $request->validated('password'));

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
            'role' => $user->role->value,
            'created_at' => $user->created_at,
        ];
    }
}
