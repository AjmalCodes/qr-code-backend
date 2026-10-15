<?php

namespace App\Models;

use App\Concerns\HasTeams;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements PasskeyUser
{
    use HasApiTokens, HasFactory, HasTeams, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name', 'email', 'phone', 'avatar', 'password', 'role', 'restaurant_id',
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
     * The restaurant this user owns (when role is Restaurant). Today's
     * creation flow always gives an owner exactly one, but restaurants.user_id
     * has no unique constraint — use restaurants() instead when a user could
     * plausibly own more than one, so none are silently dropped.
     */
    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    /**
     * Every restaurant this user owns, for the rare/future case of more than one.
     */
    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
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

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar ? Storage::disk('public')->url($this->avatar) : null);
    }
}
