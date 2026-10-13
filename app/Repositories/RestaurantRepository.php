<?php

namespace App\Repositories;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantRepository
{
    public function create(array $data): Restaurant
    {
        return Restaurant::create($data);
    }

    public function findById(int $id): ?Restaurant
    {
        return Restaurant::find($id);
    }

    public function findByEmail(string $email): ?Restaurant
    {
        return Restaurant::where('email', $email)->first();
    }

    public function findBySlug(string $slug): ?Restaurant
    {
        return Restaurant::where('slug', $slug)->first();
    }

    public function slugExists(string $slug, ?int $exceptId = null): bool
    {
        return Restaurant::where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))
            ->exists();
    }

    public function update(Restaurant $restaurant, array $data): bool
    {
        return $restaurant->update($data);
    }

    public function getAll(): Collection
    {
        return Restaurant::all();
    }

    /**
     * @param  array{search?: string, status?: string}  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return Restaurant::query()
            ->when(
                $filters['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                ),
            )
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function delete(Restaurant $restaurant): bool
    {
        return $restaurant->delete();
    }

    public function count(): int
    {
        return Restaurant::count();
    }

    public function countByStatus(string $status): int
    {
        return Restaurant::where('status', $status)->count();
    }
}
