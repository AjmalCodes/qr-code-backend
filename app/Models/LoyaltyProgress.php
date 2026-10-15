<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'loyalty_offer_id',
        'progress_count',
    ];

    protected function casts(): array
    {
        return [
            'progress_count' => 'integer',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(LoyaltyOffer::class, 'loyalty_offer_id');
    }
}
