<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Events\OrderCreated;
use App\Events\OrderStatusUpdated;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\RestaurantTable;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(private OrderRepository $orderRepository) {}

    /**
     * Place a new order for a table. Validates every product belongs to the
     * restaurant and is currently orderable, snapshots name/price onto each
     * line item, and dispatches a real-time event for the Waiter Panel.
     *
     * @param  array<int, array{product_id: int, quantity: int, special_instructions?: string}>  $items
     */
    public function createOrder(Restaurant $restaurant, RestaurantTable $table, array $items, ?int $customerId = null): Order
    {
        $productIds = collect($items)->pluck('product_id')->unique();

        $products = Product::whereIn('id', $productIds)
            ->where('restaurant_id', $restaurant->id)
            ->get()
            ->keyBy('id');

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => ["Product {$productId} does not belong to this restaurant."],
                ]);
            }

            if ($product->status->value !== 'active') {
                throw ValidationException::withMessages([
                    'items' => ["\"{$product->name}\" is not currently available."],
                ]);
            }
        }

        $order = $this->orderRepository->create([
            'restaurant_id' => $restaurant->id,
            'restaurant_table_id' => $table->id,
            'customer_id' => $customerId,
            'order_number' => $this->uniqueOrderNumber(),
            'status' => OrderStatus::Pending->value,
            'total_amount' => 0,
        ]);

        $total = 0;

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);
            $quantity = (int) $item['quantity'];
            $lineTotal = (float) $product->price * $quantity;
            $total += $lineTotal;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $product->price,
                'quantity' => $quantity,
                'special_instructions' => $item['special_instructions'] ?? null,
            ]);
        }

        $order->update(['total_amount' => $total]);
        $order = $order->fresh(['items', 'table']);

        event(new OrderCreated($order));

        return $order;
    }

    public function findForRestaurant(int $id, int $restaurantId): ?Order
    {
        return $this->orderRepository->findByIdForRestaurant($id, $restaurantId);
    }

    public function findByOrderNumberForRestaurant(string $orderNumber, int $restaurantId): ?Order
    {
        return $this->orderRepository->findByOrderNumberForRestaurant($orderNumber, $restaurantId);
    }

    public function findForCustomer(int $id, int $customerId): ?Order
    {
        return $this->orderRepository->findByIdForCustomer($id, $customerId);
    }

    /**
     * @param  array{status?: string}  $filters
     */
    public function listForRestaurant(int $restaurantId, array $filters = []): Collection
    {
        return $this->orderRepository->getAllForRestaurant($restaurantId, $filters);
    }

    public function listUpdatedSince(int $restaurantId, \DateTimeInterface $since): Collection
    {
        return $this->orderRepository->getUpdatedSince($restaurantId, $since);
    }

    /**
     * Advance an order's status, enforcing the forward-only workflow
     * (pending -> preparing -> served -> completed) and broadcasting the change.
     *
     * @throws ValidationException when the transition is not allowed
     */
    public function updateStatus(Order $order, OrderStatus $newStatus): Order
    {
        if (! in_array($newStatus, $order->status->allowedNextStatuses(), true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot move an order from \"{$order->status->value}\" to \"{$newStatus->value}\"."],
            ]);
        }

        $this->orderRepository->update($order, ['status' => $newStatus->value]);
        $order = $order->fresh(['items', 'table']);

        event(new OrderStatusUpdated($order));

        return $order;
    }

    private function uniqueOrderNumber(): string
    {
        do {
            $number = 'ORD-'.Str::upper(Str::random(8));
        } while ($this->orderRepository->orderNumberExists($number));

        return $number;
    }
}
