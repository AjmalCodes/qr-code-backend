<?php

namespace App\Services;

use App\Enums\CategoryStatus;
use App\Enums\ProductStatus;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;

class PublicMenuService
{
    /**
     * Active categories with their active, in-stock products — exactly what
     * a diner should see. Inactive categories/products and out-of-stock
     * items are excluded here rather than merely hidden client-side.
     */
    public function getMenu(Restaurant $restaurant): Collection
    {
        return $restaurant->categories()
            ->where('status', CategoryStatus::Active->value)
            ->with(['products' => fn ($query) => $query->where('status', ProductStatus::Active->value)->orderBy('name')])
            ->orderBy('sorting_order')
            ->orderBy('id')
            ->get();
    }
}
