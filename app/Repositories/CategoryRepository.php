<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Find a category by id, scoped to the given restaurant so one tenant
     * can never read or mutate another tenant's data.
     */
    public function findByIdForRestaurant(int $id, int $restaurantId): ?Category
    {
        return Category::where('restaurant_id', $restaurantId)->find($id);
    }

    public function getAllForRestaurant(int $restaurantId): Collection
    {
        return Category::where('restaurant_id', $restaurantId)
            ->orderBy('sorting_order')
            ->orderBy('id')
            ->get();
    }

    public function update(Category $category, array $data): bool
    {
        return $category->update($data);
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }
}
