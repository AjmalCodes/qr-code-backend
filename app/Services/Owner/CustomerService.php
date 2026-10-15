<?php

namespace App\Services\Owner;

use App\Repositories\CustomerRepository;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function listForRestaurant(int $restaurantId): Collection
    {
        return $this->customerRepository->getForRestaurant($restaurantId);
    }

    public function countForRestaurant(int $restaurantId): int
    {
        return $this->customerRepository->countForRestaurant($restaurantId);
    }
}
