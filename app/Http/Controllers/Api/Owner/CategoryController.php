<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreCategoryRequest;
use App\Http\Requests\Owner\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Restaurant;
use App\Services\Owner\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponses;

    public function __construct(private CategoryService $categoryService) {}

    /**
     * List all categories for the authenticated owner's restaurant, in sort
     * order, paginated via ?per_page= (defaults to 15, max 100).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $categories = $this->categoryService->list($this->tenant($request)->id, $filters['per_page'] ?? 15);

        return $this->successPaginated(CategoryResource::collection($categories), $categories);
    }

    /**
     * Create a new category for the authenticated owner's restaurant.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($this->tenant($request)->id, $request->validated());

        return $this->success(new CategoryResource($category), 'Category created successfully', 201);
    }

    /**
     * Fetch a single category, scoped to the authenticated owner's restaurant.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->find($id, $this->tenant($request)->id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        return $this->success(new CategoryResource($category));
    }

    /**
     * Update a category, scoped to the authenticated owner's restaurant.
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->find($id, $this->tenant($request)->id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $category = $this->categoryService->update($category, $request->validated());

        return $this->success(new CategoryResource($category), 'Category updated successfully');
    }

    /**
     * Delete a category, scoped to the authenticated owner's restaurant.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->find($id, $this->tenant($request)->id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $this->categoryService->delete($category);

        return $this->success(null, 'Category deleted successfully');
    }

    /**
     * Quickly toggle a category between active and inactive.
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->find($id, $this->tenant($request)->id);

        if (! $category) {
            return $this->error('Category not found.', null, 404);
        }

        $category = $this->categoryService->toggleStatus($category);

        return $this->success(new CategoryResource($category), 'Category status updated successfully');
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
