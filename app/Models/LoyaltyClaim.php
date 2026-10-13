<?php

namespace App\Models;

use App\Enums\LoyaltyClaimStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyalty_offer_id',
        'customer_id',
        'restaurant_id',
        'redemption_code',
        'status',
        'verified_by_user_id',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LoyaltyClaimStatus::class,
            'verified_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(LoyaltyOffer::class, 'loyalty_offer_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
