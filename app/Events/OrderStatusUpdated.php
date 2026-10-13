<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever staff advance an order's status. The customer's order
 * status endpoint (GET /api/v1/public/r/{slug}/orders/{id}/status) reflects
 * this on its next poll; see OrderCreated for the real-time delivery note.
 */
class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order) {}
}
