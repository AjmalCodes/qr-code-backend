<?php

namespace App\Http\Controllers\Api\Owner;

use App\Enums\ProductStatus;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\StoreProductRequest;
use App\Http\Requests\Owner\UpdateProductRequest;
use App\Http\Requests\Owner\UpdateProductStatusRequest;
use App\Http\Resources\ProductResource;
use App\Models\Restaurant;
use App\Services\Owner\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponses;

    public function __construct(private ProductService $productService) {}

    /**
     * List products for the authenticated owner's restaurant, optionally
     * filtered by ?category_id= and ?status=.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'category_id' => ['sometimes', 'integer'],
            'status' => ['sometimes', 'string'],
        ]);

        $products = $this->productService->list($this->tenant($request)->id, $filters);

        return $this->success(ProductResource::collection($products));
    }

    /**
     * Create a new product for the authenticated owner's restaurant.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(
            $this->tenant($request)->id,
            $request->safe()->except('image'),
            $request->file('image'),
        );

        return $this->success(new ProductResource($product->load('category')), 'Product created successfully', 201);
    }

    /**
     * Fetch a product with its category, scoped to the authenticated owner's restaurant.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id, $this->tenant($request)->id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        return $this->success(new ProductResource($product));
    }

    /**
     * Update a product's details, price, or category.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id, $this->tenant($request)->id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        $product = $this->productService->update(
            $product,
            $request->safe()->except('image'),
            $request->file('image'),
        );

        return $this->success(new ProductResource($product->load('category')), 'Product updated successfully');
    }

    /**
     * Delete a product, scoped to the authenticated owner's restaurant.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id, $this->tenant($request)->id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        $this->productService->delete($product);

        return $this->success(null, 'Product deleted successfully');
    }

    /**
     * Update a product's status: active, inactive, or out_of_stock.
     */
    public function updateStatus(UpdateProductStatusRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->find($id, $this->tenant($request)->id);

        if (! $product) {
            return $this->error('Product not found.', null, 404);
        }

        $product = $this->productService->updateStatus($product, $request->enum('status', ProductStatus::class));

        return $this->success(new ProductResource($product->load('category')), 'Product status updated successfully');
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
