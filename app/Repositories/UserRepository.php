<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    public function getSuperAdmins()
    {
        return User::where('role', UserRole::SuperAdmin->value)->get();
    }

    public function getRestaurants()
    {
        return User::where('role', UserRole::Restaurant->value)->get();
    }
}
