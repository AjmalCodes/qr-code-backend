<?php

namespace App\Http\Controllers\Api\Waiter;

use App\Enums\OrderStatus;
use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    use ApiResponses;

    public function __construct(private OrderService $orderService) {}

    /**
     * Current orders for the restaurant, optionally filtered by ?status=.
     * Load this once for the initial screen, then subscribe to stream().
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'status' => ['sometimes', 'string'],
        ]);

        $orders = $this->orderService->listForRestaurant($this->tenant($request)->id, $filters);

        return $this->success(OrderResource::collection($orders));
    }

    /**
     * Live event stream (Server-Sent Events) of new/updated orders for this
     * restaurant. Polls the database every 2 seconds and closes after ~55
     * seconds; a well-behaved SSE client (e.g. the browser's EventSource,
     * which auto-reconnects on close) simply reconnects to resume the feed.
     *
     * Note: browser EventSource cannot send a custom Authorization header,
     * so a frontend must consume this via `fetch()` + a manual SSE reader
     * (or the app should add short-lived signed stream tokens) rather than
     * the native EventSource API.
     */
    public function stream(Request $request): StreamedResponse
    {
        $restaurantId = $this->tenant($request)->id;

        return response()->stream(function () use ($restaurantId): void {
            // Release the request's DB connection lock/session between polls
            // isn't necessary here since each poll is a fresh, fast query.
            $lastCheckedAt = now();
            $deadline = now()->addSeconds(55);

            echo "retry: 3000\n\n";
            $this->flush();

            while (now()->lessThan($deadline)) {
                if (connection_aborted()) {
                    break;
                }

                $updated = $this->orderService->listUpdatedSince($restaurantId, $lastCheckedAt);
                $lastCheckedAt = now();

                foreach ($updated as $order) {
                    $payload = json_encode(new OrderResource($order));
                    echo "event: order\n";
                    echo "data: {$payload}\n\n";
                }

                echo ": heartbeat\n\n";
                $this->flush();

                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Advance an order's status: pending -> preparing -> served -> completed.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $newStatus = OrderStatus::tryFrom($validated['status']);

        if (! $newStatus) {
            return $this->error('Invalid order status.', ['status' => ['The status is not a recognized value.']], 422);
        }

        $order = $this->orderService->findForRestaurant($id, $this->tenant($request)->id);

        if (! $order) {
            return $this->error('Order not found.', null, 404);
        }

        try {
            $order = $this->orderService->updateStatus($order, $newStatus);
        } catch (ValidationException $e) {
            return $this->error($e->getMessage(), $e->errors(), 422);
        }

        return $this->success(new OrderResource($order), 'Order status updated successfully');
    }

    private function flush(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function tenant(Request $request): Restaurant
    {
        return $request->attributes->get('tenant_restaurant');
    }
}
