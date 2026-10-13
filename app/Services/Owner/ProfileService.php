<?php

namespace App\Services\Owner;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(private UserRepository $userRepository) {}

    public function updateProfile(User $user, array $data): User
    {
        $this->userRepository->update($user, $data);

        return $user->fresh();
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        return $this->userRepository->update($user, [
            'password' => Hash::make($newPassword),
        ]);
    }
}
