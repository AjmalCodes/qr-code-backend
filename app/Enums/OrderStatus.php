<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Preparing = 'preparing';
    case Served = 'served';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Preparing => 'Preparing',
            self::Served => 'Served',
            self::Completed => 'Completed',
        };
    }

    /**
     * The status this order may move to next. Enforces the forward-only
     * workflow: pending -> preparing -> served -> completed.
     *
     * @return array<int, self>
     */
    public function allowedNextStatuses(): array
    {
        return match ($this) {
            self::Pending => [self::Preparing],
            self::Preparing => [self::Served],
            self::Served => [self::Completed],
            self::Completed => [],
        };
    }
}
