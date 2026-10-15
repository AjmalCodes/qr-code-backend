<?php

namespace App\Services;

use App\Repositories\UserRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class RestaurantUserService
{
    public function __construct(private UserRepository $userRepository) {}

    /**
     * @param  array{search?: string}  $filters
     */
    public function search(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->searchRestaurantOwners($filters, $perPage);
    }
}
