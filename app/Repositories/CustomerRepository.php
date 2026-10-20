<?php

namespace App\Repositories;

use App\Models\Customer;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    /**
     * Registered customers who have placed at least one order with this
     * restaurant (guest orders have no customer_id, so they never appear
     * here), with per-restaurant order count, spend, and last order date.
     */
    public function getForRestaurant(int $restaurantId, int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->whereHas('orders', fn ($query) => $query->where('restaurant_id', $restaurantId))
            ->withCount(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)])
            ->withSum(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)], 'total_amount')
            ->withMax(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)], 'created_at')
            ->orderByDesc('orders_max_created_at')
            ->paginate($perPage);
    }

    /**
     * How many distinct registered customers have placed at least one order
     * with this restaurant — guest orders (no customer_id) don't count.
     * Pass $from/$to to count only customers who ordered within that range
     * (e.g. for a month-wise breakdown) instead of all-time.
     */
    public function countForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return Customer::query()
            ->whereHas('orders', function ($query) use ($restaurantId, $from, $to) {
                $query->where('restaurant_id', $restaurantId)
                    ->when($from, fn ($query) => $query->where('created_at', '>=', $from))
                    ->when($to, fn ($query) => $query->where('created_at', '<=', $to));
            })
            ->count();
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function findByVerificationToken(string $token): ?Customer
    {
        return Customer::where('email_verification_token', $token)->first();
    }

    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }
}
