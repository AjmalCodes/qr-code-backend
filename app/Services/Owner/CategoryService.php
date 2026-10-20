<?php

namespace App\Services\Owner;

use App\Enums\CategoryStatus;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(private CategoryRepository $categoryRepository) {}

    public function list(int $restaurantId): Collection
    {
        return $this->categoryRepository->getAllForRestaurant($restaurantId);
    }

    public function find(int $id, int $restaurantId): ?Category
    {
        return $this->categoryRepository->findByIdForRestaurant($id, $restaurantId);
    }

    public function create(int $restaurantId, array $data): Category
    {
        $data['restaurant_id'] = $restaurantId;
        $data['status'] ??= CategoryStatus::Active->value;

        if (isset($data['sorting_order'])) {
            $this->categoryRepository->shiftSortingOrdersForInsert($restaurantId, (int) $data['sorting_order']);
        } else {
            $data['sorting_order'] = $this->categoryRepository->countForRestaurant($restaurantId);
        }

        return $this->categoryRepository->create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (isset($data['sorting_order']) && (int) $data['sorting_order'] !== $category->sorting_order) {
            $this->categoryRepository->shiftSortingOrdersForMove(
                $category->restaurant_id,
                $category->id,
                $category->sorting_order,
                (int) $data['sorting_order'],
            );
        }

        $this->categoryRepository->update($category, $data);

        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $this->categoryRepository->delete($category);
    }

    public function toggleStatus(Category $category): Category
    {
        $newStatus = $category->status === CategoryStatus::Active
            ? CategoryStatus::Inactive
            : CategoryStatus::Active;

        $this->categoryRepository->update($category, ['status' => $newStatus->value]);

        return $category->fresh();
    }
}
