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
        'sales_channels',
        'slug',
        'viber_number',
        'telegram_username',
        'is_active',
        'is_primary',
        'subscription_tier',
        'max_products',
        'max_branches',
    ];

    public const CHANNEL_POS = 'pos';
    public const CHANNEL_ONLINE_STORE = 'online_store';
    public const CHANNEL_ONLINE_ORDERING = 'online_ordering';

    public const CHANNELS = [
        self::CHANNEL_POS,
        self::CHANNEL_ONLINE_STORE,
        self::CHANNEL_ONLINE_ORDERING,
    ];

    protected $casts = [
        'is_active'              => 'boolean',
        'is_primary'             => 'boolean',
        'max_products'           => 'integer',
        'max_branches'           => 'integer',
        'capabilities_override'  => 'array',
        'sales_channels'         => 'array',
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

    /**
     * The store's one active theme draft (isolated from the published storefront).
     */
    public function themeDraft(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StoreThemeDraft::class);
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

    public function navigationItems(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StorefrontNavigationItem::class)->orderBy('sort_order', 'asc');
    }

    public function storefrontPages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(StorefrontPage::class)->latest();
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
     * Determine if this store is running in Catalog-only mode (POS + Online Catalog, no ordering).
     */
    public function isCatalogOnly(): bool
    {
        return $this->getOperationMode() === \App\BusinessProfiles\BusinessProfile::MODE_CATALOG_ONLY;
    }

    /**
     * Resolve effective sales channels for this store.
     *
     * Precedence (Plan §5):
     * protected invariant/dependency -> explicit channel override -> operation-mode preset default.
     *
     * @return array<string, bool> Map of channel identifier to boolean state
     */
    public function getSalesChannels(): array
    {
        // 1. Preset defaults from operation_mode
        $mode = $this->getOperationMode();
        $channels = match ($mode) {
            \App\BusinessProfiles\BusinessProfile::MODE_POS_ONLY => [
                self::CHANNEL_POS => true,
                self::CHANNEL_ONLINE_STORE => false,
                self::CHANNEL_ONLINE_ORDERING => false,
            ],
            \App\BusinessProfiles\BusinessProfile::MODE_CATALOG_ONLY => [
                self::CHANNEL_POS => true,
                self::CHANNEL_ONLINE_STORE => true,
                self::CHANNEL_ONLINE_ORDERING => false,
            ],
            default => [ // omnichannel, custom, or unspecified
                self::CHANNEL_POS => true,
                self::CHANNEL_ONLINE_STORE => true,
                self::CHANNEL_ONLINE_ORDERING => true,
            ],
        };

        // 2. Explicit channel overrides (only recognized keys)
        if (is_array($this->sales_channels)) {
            foreach (self::CHANNELS as $channel) {
                if (array_key_exists($channel, $this->sales_channels)) {
                    $channels[$channel] = (bool) $this->sales_channels[$channel];
                }
            }
        }

        // 3. Protected invariants & dependencies
        // POS is protected in this phase (Plan §5)
        $channels[self::CHANNEL_POS] = true;

        // Dependency: online_store requires storefront.ecommerce capability
        if (!$this->hasCapability(\App\Capabilities\Capability::STOREFRONT_ECOMMERCE)) {
            $channels[self::CHANNEL_ONLINE_STORE] = false;
        }

        // Dependency: online_ordering requires online_store=true AND storefront.online_ordering capability
        if (!$channels[self::CHANNEL_ONLINE_STORE] || !$this->hasCapability(\App\Capabilities\Capability::STOREFRONT_ONLINE_ORDERING)) {
            $channels[self::CHANNEL_ONLINE_ORDERING] = false;
        }

        return $channels;
    }

    /**
     * Determine if the store has a specific sales channel enabled.
     */
    public function hasChannel(string $channel): bool
    {
        if (!in_array($channel, self::CHANNELS, true)) {
            return false;
        }

        $channels = $this->getSalesChannels();

        return !empty($channels[$channel]);
    }

    /**
     * Alias for hasChannel().
     */
    public function hasSalesChannel(string $channel): bool
    {
        return $this->hasChannel($channel);
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
