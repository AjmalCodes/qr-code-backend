<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'plan_name',
        'amount',
        'status',
        'starts_at',
        'ends_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Active by status AND not past its end date — a subscription whose
     * ends_at has lapsed is no longer meaningfully "active" even if a
     * scheduled job hasn't flipped its status row yet.
     */
    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::Active
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    /**
     * Days left until expiry — null when there is no end date (ongoing),
     * 0 once ends_at has passed rather than a negative number.
     *
     * Rounds up (ceiling) rather than truncating: a subscription set to
     * expire in exactly 10 days should still read "10" a moment later, not
     * drop to "9" the instant any sub-second time passes.
     */
    protected function remainingDays(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->ends_at === null) {
                return null;
            }

            if ($this->ends_at->isPast()) {
                return 0;
            }

            return (int) ceil(now()->diffInSeconds($this->ends_at) / 86400);
        });
    }
}
