<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StorefrontSetting extends Model
{
    use HasFactory;

    protected $table = 'storefront_settings';

    protected $fillable = [
        'store_id',
        'store_name',
        'tagline',
        'logo_path',
        'storefront_logo_path',
        'admin_logo_path',
        'favicon_path',
        'address',
        'phone',
        'opening_hours',
        'viber_number',
        'telegram_username',
        'facebook_url',
        'youtube_url',
        'tiktok_url',
        'map_enabled',
        'google_maps_url',
        'map_latitude',
        'map_longitude',
        'map_title',
        'map_embed_enabled',
        'chat_button_label',
        'chat_button_url',
        'chat_button_icon',
        'chat_button_icon_path',
        'chat_channels',
        'delivery_info',
        'payment_info',
        'footer_ad_text',
        'default_language',
        'pos_hold_expiry_hours',
        'pos_override_pin_threshold',
        'how_to_intro',
        'how_to_steps',
        'how_to_videos',
    ];

    protected $casts = [
        'how_to_steps' => 'array',
        'how_to_videos' => 'array',
        'chat_channels' => 'array',
        'map_enabled' => 'boolean',
        'map_embed_enabled' => 'boolean',
        'map_latitude' => 'float',
        'map_longitude' => 'float',
        'pos_hold_expiry_hours' => 'integer',
        'pos_override_pin_threshold' => 'integer',
    ];

    /**
     * POS held-sale auto-expiry window in hours for this store. Falls back
     * to the global 24h default when unset; 0 disables auto-expiry.
     */
    public function posHoldExpiryHours(): int
    {
        $hours = $this->pos_hold_expiry_hours;

        return $hours === null ? 24 : (int) $hours;
    }

    /**
     * Max price-override discount (percent of the tier price) a cashier may
     * apply without manager approval. null/0 = no PIN required. When set, an
     * override deeper than this needs a store manager/owner POS PIN.
     */
    public function posOverridePinThreshold(): ?int
    {
        $threshold = $this->pos_override_pin_threshold;

        return ($threshold === null || (int) $threshold <= 0) ? null : (int) $threshold;
    }

    /**
     * Exact map link — the configured Google Maps URL, or the legacy
     * address-search fallback when no exact location is configured yet.
     */
    public function mapUrl(): ?string
    {
        if (! empty($this->google_maps_url)) {
            return $this->google_maps_url;
        }

        if (! empty($this->address)) {
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($this->address);
        }

        return null;
    }

    /**
     * Google Maps directions link — exact pin when configured, else address.
     */
    public function mapDirectionsUrl(): ?string
    {
        $query = null;
        if (! empty($this->map_latitude) && ! empty($this->map_longitude)) {
            $query = $this->map_latitude . ',' . $this->map_longitude;
        } elseif (! empty($this->google_maps_url)) {
            return $this->google_maps_url;
        } elseif (! empty($this->address)) {
            $query = $this->address;
        }

        return $query ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($query) : null;
    }

    /**
     * Privacy-friendly embed src (youtube-style lazy iframe). Requires exact
     * coordinates; falls back to null so no broken iframe is ever rendered.
     */
    public function mapEmbedSrc(): ?string
    {
        if (empty($this->map_latitude) || empty($this->map_longitude)) {
            return null;
        }

        // Street-level zoom for a shop pin (the owner's Google Maps share link
        // pinned the store at 21z; 17 shows the shop block clearly on a phone).
        $zoom = 17;
        $q = $this->map_latitude . ',' . $this->map_longitude;

        return 'https://www.google.com/maps?q=' . rawurlencode($q) . '&z=' . $zoom . '&output=embed';
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Effective Storefront (horizontal) logo path — the dedicated asset, or
     * the legacy `logo_path` when no dedicated Storefront logo was uploaded.
     */
    public function storefrontLogo(): ?string
    {
        return $this->storefront_logo_path ?: $this->logo_path;
    }

    /**
     * Effective Admin (square icon) logo path — dedicated asset, falling back
     * to the Storefront logo and then the legacy logo.
     */
    public function adminLogo(): ?string
    {
        return $this->admin_logo_path ?: $this->storefront_logo_path ?: $this->logo_path;
    }

    /**
     * Effective favicon/app-icon path — dedicated asset, falling back through
     * the Admin logo, Storefront logo and legacy logo in that order.
     */
    public function favicon(): ?string
    {
        return $this->favicon_path ?: $this->admin_logo_path ?: $this->storefront_logo_path ?: $this->logo_path;
    }
}
