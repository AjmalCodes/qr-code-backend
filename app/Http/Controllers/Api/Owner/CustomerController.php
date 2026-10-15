<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Restaurant;
use App\Services\Owner\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use ApiResponses;

    public function __construct(private CustomerService $customerService) {}

    /**
     * Registered customers who have ordered from this restaurant, with
     * their order count, total spend, and last order date. Guest orders
     * (no account) are not included — there is no customer identity to show.
     */
    public function index(Request $request): JsonResponse
    {
        $customers = $this->customerService->listForRestaurant($this->tenant($request)->id);

        return $this->success($customers->map(fn (Customer $c) => [
            'id' => $c->id,
            'name' => $c->name,
            'email' => $c->email,
            'orders_count' => $c->orders_count,
            'total_spent' => (float) ($c->orders_sum_total_amount ?? 0),
            'last_order_at' => $c->orders_max_created_at,
        ]));
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
