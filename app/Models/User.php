<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    /**
     * Web push subscriptions registered by this user's browser(s).
     */
    public function pushSubscriptions(): HasMany
    {
        return $this->hasMany(config('webpush.model'));
    }

    /**
     * Route web push notifications to this user's subscriptions.
     */
    public function routeNotificationForWebPush(): Collection
    {
        return $this->pushSubscriptions()->get();
    }

    /**
     * Stores where the user has an active membership.
     * Excludes pending/suspended memberships.
     */
    public function activeStores(): BelongsToMany
    {
        return $this->stores()->wherePivot('status', 'active');
    }

    /**
     * The user's primary active store (first one found).
     * Returns null for platform owners or users with no active membership.
     */
    public function getPrimaryStore(): ?Store
    {
        return $this->activeStores()->first();
    }

    public function isPlatformOwner(): bool
    {
        return $this->role === 'platform_owner';
    }

    /**
     * Whether the user can access store admin areas
     * (store_manager or staff role in any active store).
     */
    public function isStoreAdmin(): bool
    {
        return $this->activeStores()
            ->wherePivotIn('role', ['store_manager', 'staff'])
            ->exists();
    }

    public function getStoreMembership(int $storeId): ?object
    {
        return $this->stores()->where('store_id', $storeId)->first()?->pivot;
    }

    public function getStoreRole(int $storeId): ?string
    {
        if ($this->isPlatformOwner()) {
            return 'store_manager';
        }

        $membership = $this->getStoreMembership($storeId);

        return ($membership && $membership->status === 'active') ? $membership->role : null;
    }

    public function hasStoreRole(int $storeId, array|string $roles): bool
    {
        if ($this->isPlatformOwner()) {
            return true;
        }

        $userStoreRole = $this->getStoreRole($storeId);

        if (!$userStoreRole) {
            return false;
        }

        $allowedRoles = (array) $roles;

        return in_array($userStoreRole, $allowedRoles, true);
    }
}
