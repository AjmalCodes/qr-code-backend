<?php

namespace App\Services\Owner;

use App\Models\RestaurantTable;
use App\Repositories\RestaurantTableRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TableService
{
    public function __construct(private RestaurantTableRepository $tableRepository) {}

    public function list(int $restaurantId): Collection
    {
        return $this->tableRepository->getAllForRestaurant($restaurantId);
    }

    public function find(int $id, int $restaurantId): ?RestaurantTable
    {
        return $this->tableRepository->findByIdForRestaurant($id, $restaurantId);
    }

    public function create(int $restaurantId, array $data): RestaurantTable
    {
        return $this->tableRepository->create([
            'restaurant_id' => $restaurantId,
            'label' => $data['label'],
            'qr_token' => $this->uniqueToken(),
        ]);
    }

    public function update(RestaurantTable $table, array $data): RestaurantTable
    {
        $this->tableRepository->update($table, $data);

        return $table->fresh();
    }

    public function delete(RestaurantTable $table): bool
    {
        return $this->tableRepository->delete($table);
    }

    /**
     * Invalidate the current printed QR code (e.g. a lost/compromised table
     * card) by issuing a new token. The old code stops resolving immediately.
     */
    public function regenerateToken(RestaurantTable $table): RestaurantTable
    {
        $this->tableRepository->update($table, ['qr_token' => $this->uniqueToken()]);

        return $table->fresh();
    }

    private function uniqueToken(): string
    {
        do {
            $token = Str::random(24);
        } while ($this->tableRepository->tokenExists($token));

        return $token;
    }
}
