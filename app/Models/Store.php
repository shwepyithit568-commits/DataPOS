<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_type',
        'business_profile',
        'operation_mode',
        'capabilities_override',
        'slug',
        'viber_number',
        'telegram_username',
        'is_active',
        'is_primary',
        'subscription_tier',
        'max_products',
        'max_branches',
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'is_primary'             => 'boolean',
        'max_products'           => 'integer',
        'max_branches'           => 'integer',
        'capabilities_override'  => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function setting(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StorefrontSetting::class);
    }

    public function themeRevisions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreThemeRevision::class)->latest('revision_number');
    }

    public function homeBanners(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(HomeBanner::class)->orderBy('sort_order', 'asc');
    }

    public function paymentMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StorePaymentMethod::class);
    }

    public function deliveryMethods(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StoreDeliveryMethod::class);
    }

    public function productMasterPresets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ProductMasterPreset::class)->orderBy('sort_order')->orderBy('name');
    }

    /* ------------------------------------------------------------------ */
    /*  POS locations (target-design §2.11)                                */
    /* ------------------------------------------------------------------ */

    public function branches(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\POS\Models\Branch::class);
    }

    public function warehouses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\POS\Models\Warehouse::class);
    }

    public function defaultBranch(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\POS\Models\Branch::class)->where('is_default', true);
    }

    public function defaultWarehouse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(\App\POS\Models\Warehouse::class)->where('is_default', true);
    }

    /* ------------------------------------------------------------------ */
    /*  Business Profile & Capabilities (Phase 1 Foundation)               */
    /* ------------------------------------------------------------------ */

    /**
     * Get the standardized business profile of this store.
     */
    public function getBusinessProfile(): string
    {
        return \App\BusinessProfiles\BusinessProfileRegistry::resolveProfile(
            $this->business_profile,
            $this->business_type
        );
    }

    /**
     * Get the operation mode (omnichannel vs pos_only).
     */
    public function getOperationMode(): string
    {
        return $this->operation_mode ?: \App\BusinessProfiles\BusinessProfile::MODE_OMNICHANNEL;
    }

    /**
     * Determine if this store is running in POS-only mode.
     */
    public function isPosOnly(): bool
    {
        return $this->getOperationMode() === \App\BusinessProfiles\BusinessProfile::MODE_POS_ONLY;
    }

    /**
     * Determine if this store is running in Omnichannel mode (POS + Storefront).
     */
    public function isOmnichannel(): bool
    {
        return $this->getOperationMode() === \App\BusinessProfiles\BusinessProfile::MODE_OMNICHANNEL;
    }

    /**
     * Resolve effective capabilities list for this store.
     *
     * @return array<string, bool> Map of capability identifier to boolean state
     */
    public function getCapabilities(): array
    {
        $profile = $this->getBusinessProfile();
        $defaultList = \App\BusinessProfiles\BusinessProfileRegistry::getDefaultCapabilities($profile);

        $capabilities = [];
        foreach ($defaultList as $cap) {
            $capabilities[$cap] = true;
        }

        // Apply operation mode constraints (POS-Only disables public ecommerce by default)
        if ($this->isPosOnly()) {
            $capabilities[\App\Capabilities\Capability::STOREFRONT_ECOMMERCE] = false;
            $capabilities[\App\Capabilities\Capability::STOREFRONT_ONLINE_ORDERING] = false;
        }

        // Apply custom overrides stored on the store record (if any)
        if (is_array($this->capabilities_override)) {
            foreach ($this->capabilities_override as $cap => $enabled) {
                $capabilities[$cap] = (bool) $enabled;
            }
        }

        return $capabilities;
    }

    /**
     * Determine if the store has a specific capability enabled.
     */
    public function hasCapability(string $capability): bool
    {
        $caps = $this->getCapabilities();

        return !empty($caps[$capability]);
    }

    /**
     * Max allowed products under active subscription plan.
     */
    public function maxProducts(): ?int
    {
        return \App\Services\SubscriptionPlanService::getMaxProducts($this);
    }

    /**
     * Max allowed branches under active subscription plan.
     */
    public function maxBranches(): ?int
    {
        return \App\Services\SubscriptionPlanService::getMaxBranches($this);
    }

    /**
     * Check if store can add a new product within quota.
     */
    public function canAddProduct(): bool
    {
        return \App\Services\SubscriptionPlanService::canAddProduct($this);
    }

    /**
     * Check if store can add a new branch within quota.
     */
    public function canAddBranch(): bool
    {
        return \App\Services\SubscriptionPlanService::canAddBranch($this);
    }
}
