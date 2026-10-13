<?php

namespace App\Services\Owner;

use App\Enums\UserRole;
use App\Mail\StaffAccountCreated;
use App\Models\Restaurant;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class StaffService
{
    public function __construct(private UserRepository $userRepository) {}

    public function list(int $restaurantId): Collection
    {
        return User::where('restaurant_id', $restaurantId)->where('role', UserRole::Waiter->value)->get();
    }

    public function find(int $id, int $restaurantId): ?User
    {
        return User::where('restaurant_id', $restaurantId)
            ->where('role', UserRole::Waiter->value)
            ->find($id);
    }

    public function create(Restaurant $restaurant, array $data): User
    {
        $temporaryPassword = Str::random(12);

        $staff = $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($temporaryPassword),
            'role' => UserRole::Waiter->value,
            'restaurant_id' => $restaurant->id,
        ]);

        Mail::to($staff->email)->send(new StaffAccountCreated($staff, $restaurant, $temporaryPassword));

        return $staff;
    }

    public function update(User $staff, array $data): User
    {
        $this->userRepository->update($staff, $data);

        return $staff->fresh();
    }

    public function delete(User $staff): bool
    {
        return $staff->delete();
    }
}
