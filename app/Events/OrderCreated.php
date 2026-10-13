<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the moment a customer places an order. The Waiter Panel picks this
 * up via its polling SSE stream (GET /api/waiter/orders/stream), which
 * queries for orders updated since the client's last poll.
 *
 * To upgrade to true push delivery (WebSockets), implement ShouldBroadcast
 * here once a broadcast driver (e.g. laravel/reverb) is installed — no
 * other code in the order flow needs to change.
 */
class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
