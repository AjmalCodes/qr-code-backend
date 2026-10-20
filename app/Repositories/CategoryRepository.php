<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function getAllForRestaurant(int $restaurantId, int $perPage = 15): LengthAwarePaginator
    {
        return Category::where('restaurant_id', $restaurantId)
            ->orderBy('sorting_order')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function countForRestaurant(int $restaurantId): int
    {
        return Category::where('restaurant_id', $restaurantId)->count();
    }

    /**
     * Makes room for a category being inserted (or created) at $position by
     * pushing every category already at or past it one step back — so
     * giving a new category the same sorting_order as an existing one bumps
     * that one (and everything after it) down, instead of leaving duplicates.
     */
    public function shiftSortingOrdersForInsert(int $restaurantId, int $position): void
    {
        Category::where('restaurant_id', $restaurantId)
            ->where('sorting_order', '>=', $position)
            ->increment('sorting_order');
    }

    /**
     * Closes the gap left by moving an existing category from $oldPosition to
     * $newPosition, shifting only the categories in between — e.g. moving a
     * category from position 1 to 3 pulls positions 2 and 3 back by one,
     * rather than just bumping whatever already sits at 3.
     */
    public function shiftSortingOrdersForMove(int $restaurantId, int $categoryId, int $oldPosition, int $newPosition): void
    {
        $query = Category::where('restaurant_id', $restaurantId)->where('id', '!=', $categoryId);

        if ($newPosition > $oldPosition) {
            $query->whereBetween('sorting_order', [$oldPosition + 1, $newPosition])->decrement('sorting_order');
        } else {
            $query->whereBetween('sorting_order', [$newPosition, $oldPosition - 1])->increment('sorting_order');
        }
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
