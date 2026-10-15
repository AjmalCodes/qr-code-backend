<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'product_id',
        'title',
        'description',
        'buy_quantity',
        'get_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'buy_quantity' => 'integer',
            'get_quantity' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(LoyaltyClaim::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LoyaltyProgress::class);
    }
}
