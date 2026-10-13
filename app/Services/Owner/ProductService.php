<?php

namespace App\Services\Owner;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    private const IMAGE_DIRECTORY = 'products';

    public function __construct(private ProductRepository $productRepository) {}

    /**
     * @param  array{category_id?: int, status?: string}  $filters
     */
    public function list(int $restaurantId, array $filters = []): Collection
    {
        return $this->productRepository->getAllForRestaurant($restaurantId, $filters);
    }

    public function find(int $id, int $restaurantId): ?Product
    {
        return $this->productRepository->findByIdForRestaurant($id, $restaurantId);
    }

    public function create(int $restaurantId, array $data, ?UploadedFile $image = null): Product
    {
        $data['restaurant_id'] = $restaurantId;
        $data['status'] ??= ProductStatus::Active->value;

        if ($image) {
            $data['image'] = $image->store(self::IMAGE_DIRECTORY, 'public');
        }

        return $this->productRepository->create($data);
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        if ($image) {
            $data['image'] = $this->replaceImage($product, $image);
        }

        $this->productRepository->update($product, $data);

        return $product->fresh();
    }

    public function delete(Product $product): bool
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $this->productRepository->delete($product);
    }

    public function updateStatus(Product $product, ProductStatus $status): Product
    {
        $this->productRepository->update($product, ['status' => $status->value]);

        return $product->fresh();
    }

    private function replaceImage(Product $product, UploadedFile $image): string
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        return $image->store(self::IMAGE_DIRECTORY, 'public');
    }
}
