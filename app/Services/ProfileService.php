<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

/**
 * Account-settings logic shared by every authenticated role (Super Admin,
 * Restaurant Owner, Waiter) — updating one's own profile and password is
 * identical regardless of role, so this is deliberately role-agnostic.
 */
class ProfileService
{
    private const AVATAR_DIRECTORY = 'avatars';

    public function __construct(private UserRepository $userRepository) {}

    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        if ($avatar) {
            $data['avatar'] = $this->replaceAvatar($user, $avatar);
        }

        $this->userRepository->update($user, $data);

        return $user->fresh();
    }

    public function changePassword(User $user, string $newPassword): bool
    {
        return $this->userRepository->update($user, [
            'password' => Hash::make($newPassword),
        ]);
    }

    private function replaceAvatar(User $user, UploadedFile $avatar): string
    {
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        return $avatar->store(self::AVATAR_DIRECTORY, 'public');
    }
}
