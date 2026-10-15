<?php

namespace App\Repositories;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;

class RestaurantTableRepository
{
    public function create(array $data): RestaurantTable
    {
        return RestaurantTable::create($data);
    }

    public function findByIdForRestaurant(int $id, int $restaurantId): ?RestaurantTable
    {
        return RestaurantTable::where('restaurant_id', $restaurantId)->find($id);
    }

    public function findByQrToken(string $qrToken): ?RestaurantTable
    {
        return RestaurantTable::where('qr_token', $qrToken)->first();
    }

    public function getAllForRestaurant(int $restaurantId): Collection
    {
        return RestaurantTable::where('restaurant_id', $restaurantId)->orderBy('label')->get();
    }

    public function countForRestaurant(int $restaurantId): int
    {
        return RestaurantTable::where('restaurant_id', $restaurantId)->count();
    }

    public function update(RestaurantTable $table, array $data): bool
    {
        return $table->update($data);
    }

    public function delete(RestaurantTable $table): bool
    {
        return $table->delete();
    }

    public function tokenExists(string $token): bool
    {
        return RestaurantTable::where('qr_token', $token)->exists();
    }
}
