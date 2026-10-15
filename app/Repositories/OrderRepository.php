<?php

namespace App\Repositories;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;

class OrderRepository
{
    // Typed against CarbonInterface (not the concrete Illuminate\Support\Carbon
    // class) because this app's AppServiceProvider calls Date::use(CarbonImmutable::class),
    // so now()/today() return CarbonImmutable — a sibling, not a subclass, of Carbon.
    public function countForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return Order::where('restaurant_id', $restaurantId)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->count();
    }

    public function countForRestaurantByStatus(int $restaurantId, string $status, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return Order::where('restaurant_id', $restaurantId)
            ->where('status', $status)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->count();
    }

    public function sumRevenueForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): float
    {
        return (float) Order::where('restaurant_id', $restaurantId)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->sum('total_amount');
    }

    /**
     * Order counts grouped by table, to pair against QR scan counts per
     * table (scan-to-order conversion).
     *
     * @return BaseCollection<int, object{restaurant_table_id: int, orders: int}>
     */
    public function countsByTableForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): BaseCollection
    {
        return Order::query()
            ->selectRaw('restaurant_table_id, count(*) as orders')
            ->where('restaurant_id', $restaurantId)
            ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('created_at', '<=', $to))
            ->groupBy('restaurant_table_id')
            ->get();
    }

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
        return Order::with(['items', 'table', 'restaurant'])->where('customer_id', $customerId)->find($id);
    }

    /**
     * Every order this customer has placed, across all restaurants — their
     * full order history for "my orders".
     */
    public function getAllForCustomer(int $customerId): Collection
    {
        return Order::with(['items', 'table', 'restaurant'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
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
     * @param  array{status?: string, from?: CarbonInterface, to?: CarbonInterface}  $filters
     */
    public function getAllForRestaurant(int $restaurantId, array $filters = []): Collection
    {
        return Order::with(['items', 'table', 'customer'])
            ->where('restaurant_id', $restaurantId)
            ->when(isset($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(isset($filters['from']), fn ($query) => $query->where('created_at', '>=', $filters['from']))
            ->when(isset($filters['to']), fn ($query) => $query->where('created_at', '<=', $filters['to']))
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
