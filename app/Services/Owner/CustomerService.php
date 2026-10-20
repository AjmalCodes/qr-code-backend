<?php

namespace App\Services\Owner;

use App\Repositories\CustomerRepository;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function listForRestaurant(int $restaurantId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->customerRepository->getForRestaurant($restaurantId, $perPage);
    }

    public function countForRestaurant(int $restaurantId, ?CarbonInterface $from = null, ?CarbonInterface $to = null): int
    {
        return $this->customerRepository->countForRestaurant($restaurantId, $from, $to);
    }
}
