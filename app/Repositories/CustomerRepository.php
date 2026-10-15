<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Collection;

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
    public function getForRestaurant(int $restaurantId): Collection
    {
        return Customer::query()
            ->whereHas('orders', fn ($query) => $query->where('restaurant_id', $restaurantId))
            ->withCount(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)])
            ->withSum(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)], 'total_amount')
            ->withMax(['orders' => fn ($query) => $query->where('restaurant_id', $restaurantId)], 'created_at')
            ->orderByDesc('orders_max_created_at')
            ->get();
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
