<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Restaurant;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct(private OrderService $orderService) {}

    /**
     * List orders for the authenticated owner's restaurant. Supports
     * ?status=, ?from=, ?to= (Y-m-d).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        if (isset($filters['from'])) {
            $filters['from'] = Carbon::parse($filters['from'])->startOfDay();
        }

        if (isset($filters['to'])) {
            $filters['to'] = Carbon::parse($filters['to'])->endOfDay();
        }

        $orders = $this->orderService->listForRestaurant($this->tenant($request)->id, $filters);

        return $this->success(OrderResource::collection($orders));
    }

    /**
     * A single order's full detail (items, table, status, totals).
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $order = $this->orderService->findForRestaurant($id, $this->tenant($request)->id);

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        return $this->success(new OrderResource($order));
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
