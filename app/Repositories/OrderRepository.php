<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;

class OrderRepository
{
    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function findByIdForRestaurant(int $id, int $restaurantId): ?Order
    {
        return Order::with(['items', 'table'])->where('restaurant_id', $restaurantId)->find($id);
    }

    public function findByIdForCustomer(int $id, int $customerId): ?Order
    {
        return Order::with('items')->where('customer_id', $customerId)->find($id);
    }

    /**
     * Looked up by the unguessable public order_number, not the sequential
     * id, so a guest can poll status without exposing other tenants' orders.
     */
    public function findByOrderNumberForRestaurant(string $orderNumber, int $restaurantId): ?Order
    {
        return Order::where('restaurant_id', $restaurantId)->where('order_number', $orderNumber)->first();
    }

    public function orderNumberExists(string $orderNumber): bool
    {
        return Order::where('order_number', $orderNumber)->exists();
    }

    /**
     * @param  array{status?: string}  $filters
     */
    public function getAllForRestaurant(int $restaurantId, array $filters = []): Collection
    {
        return Order::with(['items', 'table'])
            ->where('restaurant_id', $restaurantId)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->latest()
            ->get();
    }

    /**
     * Orders created or updated after the given moment, for polling-based
     * live sync on the Waiter Panel.
     */
    public function getUpdatedSince(int $restaurantId, \DateTimeInterface $since): Collection
    {
        return Order::with(['items', 'table'])
            ->where('restaurant_id', $restaurantId)
            ->where('updated_at', '>', $since)
            ->orderBy('updated_at')
            ->get();
    }

    public function update(Order $order, array $data): bool
    {
        return $order->update($data);
    }
}
