<?php

namespace App\Repositories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

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

    /**
     * One row per restaurant-owner account, each with every restaurant they
     * own eager-loaded as a nested array — driven by the user, not a
     * restaurant-joined query, so an owner of several restaurants (or, today,
     * exactly one) still appears exactly once, never repeated per restaurant.
     *
     * @param  array{search?: string}  $filters
     */
    public function searchRestaurantOwners(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return User::where('role', UserRole::Restaurant->value)
            ->with('restaurants')
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('restaurants', fn ($r) => $r
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                    )
                ),
            )
            ->latest()
            ->paginate($perPage);
    }
}
