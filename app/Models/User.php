<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'pos_pin',
        'role',
    ];

    protected $hidden = [
        'password',
        'pos_pin',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pos_pin' => 'hashed',
        ];
    }

    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class)
            ->withPivot(['role', 'status', 'staff_role_id', 'custom_permissions'])
            ->withTimestamps();
    }

    /**
     * Normalize a Myanmar phone cell for identity matching: strip every
     * non-digit and a leading "09" → "9..." ("09 123 456 789" /
     * "09123456789" / "+95 9123456789" → "9123456789"). Same rule as
     * CustomerImportService, so POS, ecommerce and imports agree on who
     * a phone number belongs to.
     */
    public static function normalizePhone(mixed $value): string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $value);

        return $digits === null ? '' : ltrim($digits, '0');
    }

    /**
     * Find the single user a phone number belongs to, regardless of how the
     * stored value was formatted over the app's history (imports store digits
     * without the leading 0, seeded/legacy rows keep "09...", POS and register
     * store what was typed). Comparison is on the normalized form.
     */
    public static function findByNormalizedPhone(mixed $phone): ?self
    {
        $normalized = static::normalizePhone($phone);
        if ($normalized === '') {
            return null;
        }

        // Narrow by the trailing 3 digits — the one group that stays
        // contiguous in every Myanmar phone spelling ("09 123 456 789",
        // "09123456789", "+95 912 345 6789" all end in "789") — then
        // exact-match on the normalized form so every legacy spelling is
        // found exactly once. The LIKE is over a customers-sized table, so
        // the few trailing-digit collisions are cheap to filter in PHP.
        $tail = substr($normalized, -3);

        return static::query()
            ->where('phone', 'like', '%' . $tail)
            ->get()
            ->first(fn (self $u) => static::normalizePhone($u->phone) === $normalized);
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
     * (store_owner, store_manager or staff role in any active store).
     */
    public function isStoreAdmin(): bool
    {
        return $this->activeStores()
            ->wherePivotIn('role', ['store_owner', 'store_manager', 'staff'])
            ->exists();
    }

    public function isStoreOwner(int $storeId): bool
    {
        if ($this->isPlatformOwner()) {
            return true;
        }

        return $this->getStoreRole($storeId) === 'store_owner';
    }

    public function isStoreManager(int $storeId): bool
    {
        if ($this->isPlatformOwner()) {
            return true;
        }

        return in_array($this->getStoreRole($storeId), ['store_owner', 'store_manager'], true);
    }

    public function getStoreMembership(int $storeId): ?object
    {
        return $this->stores()->where('store_id', $storeId)->first()?->pivot;
    }

    /**
     * Whether this user's POS PIN (hashed in `pos_pin`) matches the given
     * plaintext PIN — used for manager approval of deep price overrides.
     */
    public function posPinMatches(string $pin): bool
    {
        return $this->pos_pin !== null && Hash::check($pin, $this->pos_pin);
    }

    public function getStoreRole(int $storeId): ?string
    {
        if ($this->isPlatformOwner()) {
            return 'store_owner';
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

        // store_owner has hierarchical access to store_manager and staff
        if ($userStoreRole === 'store_owner') {
            return true;
        }

        // store_manager has hierarchical access to staff
        if ($userStoreRole === 'store_manager' && in_array('staff', $allowedRoles, true)) {
            return true;
        }

        return in_array($userStoreRole, $allowedRoles, true);
    }
}
