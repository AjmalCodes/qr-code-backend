<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private UserRepository $userRepository) {}

    public function login(string $email, string $password): ?User
    {
        $user = $this->userRepository->findByEmail($email);

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }

    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
        $data['role'] = UserRole::User->value;

        return $this->userRepository->create($data);
    }
}
