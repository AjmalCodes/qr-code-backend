<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use App\Repositories\RestaurantTableRepository;
use App\Services\OrderService;
use App\Services\RestaurantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct(
        private RestaurantService $restaurantService,
        private RestaurantTableRepository $tableRepository,
        private OrderService $orderService,
    ) {}

    /**
     * Place a new order from the QR menu — no account required. The table
     * is identified by the QR code's token, never a raw table id. Submitting
     * instantly makes the order visible to the Waiter Panel's live stream.
     */
    public function store(PlaceOrderRequest $request, string $slug): JsonResponse
    {
        $restaurant = $this->restaurantService->findActiveBySlug($slug);

        if (! $restaurant) {
            return $this->error('Restaurant not found.', null, 404);
        }

        $table = $this->tableRepository->findByQrToken($request->validated('table_token'));

        // A logged-in customer gets their order linked to their account. Sanctum
        // resolves tokens polymorphically regardless of guard name, so confirm
        // the resolved model is actually a Customer before trusting its id.
        $authenticatedCustomer = $request->user('customer');
        $customerId = $authenticatedCustomer instanceof Customer ? $authenticatedCustomer->id : null;

        try {
            $order = $this->orderService->createOrder($restaurant, $table, $request->validated('items'), $customerId);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success(new OrderResource($order), 'Order placed successfully', 201);
    }

    /**
     * Live status for a previously placed order, looked up by its public
     * order_number (not the database id) so a guest can track their order
     * without an account while a stranger cannot enumerate other orders.
     */
    public function status(Request $request, string $slug, string $orderNumber): JsonResponse
    {
        $restaurant = $this->restaurantService->findActiveBySlug($slug);

        if (! $restaurant) {
            return $this->error('Restaurant not found.', null, 404);
        }

        $order = $this->orderService->findByOrderNumberForRestaurant($orderNumber, $restaurant->id);

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        return $this->success([
            'order_number' => $order->order_number,
            'status' => $order->status->value,
            'updated_at' => $order->updated_at,
        ]);
    }
}
