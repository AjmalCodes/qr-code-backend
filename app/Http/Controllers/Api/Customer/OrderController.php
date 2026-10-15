<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct(private OrderService $orderService) {}

    /**
     * The authenticated customer's full order history, across every
     * restaurant they've ordered from.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        return $this->success(OrderResource::collection($this->orderService->listForCustomer($customer->id)));
    }

    /**
     * A single order's detail — only if it belongs to the authenticated customer.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->user('customer');

        $order = $this->orderService->findForCustomer($id, $customer->id);

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        return $this->success(new OrderResource($order));
    }
}
