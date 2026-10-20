<?php

namespace App\Services\Owner;

use App\Repositories\CustomerRepository;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function listForRestaurant(int $restaurantId): Collection
    {
        return $this->customerRepository->getForRestaurant($restaurantId);
    }

    public function countForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return $this->customerRepository->countForRestaurant($restaurantId, $from, $to);
    }
}
