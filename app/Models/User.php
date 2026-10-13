<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role', 'restaurant_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'role' => UserRole::class,
        ];
    }

    /**
     * The restaurant this user owns (when role is Restaurant).
     */
    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    /**
     * The restaurant this user works for as staff (when role is Waiter).
     */
    public function staffRestaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isRestaurant(): bool
    {
        return $this->role === UserRole::Restaurant;
    }

    public function isWaiter(): bool
    {
        return $this->role === UserRole::Waiter;
    }

    /**
     * The restaurant this user is scoped to, regardless of whether they are
     * the owner or a staff member. Used by tenancy-resolving middleware.
     */
    public function tenantRestaurant(): ?Restaurant
    {
        return $this->isWaiter() ? $this->staffRestaurant : $this->restaurant;
    }
}
