<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Find a product by id, scoped to the given restaurant so one tenant
     * can never read or mutate another tenant's data.
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?Product
    {
        return Product::with('category')->where('restaurant_id', $restaurantId)->find($id);
    }

    /**
     * @param  array{category_id?: int, status?: string}  $filters
     */
    public function getAllForRestaurant(int $restaurantId, array $filters = []): Collection
    {
        return Product::with('category')
            ->where('restaurant_id', $restaurantId)
            ->when(isset($filters['category_id']), fn ($query) => $query->where('category_id', $filters['category_id']))
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    public function countForRestaurant(int $restaurantId): int
    {
        return Product::where('restaurant_id', $restaurantId)->count();
    }

    public function countForRestaurantByStatus(int $restaurantId, string $status): int
    {
        return Product::where('restaurant_id', $restaurantId)->where('status', $status)->count();
    }

    public function update(Product $product, array $data): bool
    {
        return $product->update($data);
    }

    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
