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
        'pos_settings',
        'currency_settings',
        'how_to_intro',
        'how_to_steps',
        'how_to_videos',
        // Storefront theme / colour-scheme fields
        'theme_preset',
        'theme_primary_color',
        'theme_accent_color',
        'theme_header_bg',
        'theme_body_bg',
        'theme_glow_style',
        'theme_dark_mode',
        'font_preset',
        'grid_density',
    ];

    protected $casts = [
        'how_to_steps' => 'array',
        'how_to_videos' => 'array',
        'chat_channels' => 'array',
        'pos_settings' => 'array',
        'currency_settings' => 'array',
        'map_enabled' => 'boolean',
        'map_embed_enabled' => 'boolean',
        'map_latitude' => 'float',
        'map_longitude' => 'float',
        'pos_hold_expiry_hours' => 'integer',
        'pos_override_pin_threshold' => 'integer',
    ];

    /**
     * Get a granular POS configuration setting with default fallback.
     */
    public function getPosSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->pos_settings ?? [];
        return data_get($settings, $key, $default);
    }

    /**
     * Get a granular Currency & Accounting format setting with default fallback.
     */
    public function getCurrencySetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->currency_settings ?? [];
        return data_get($settings, $key, $default);
    }

    /**
     * Format an amount using this store's configured currency/accounting rules.
     */
    public function formatCurrency(float|int|string|null $amount): string
    {
        return \App\Support\CurrencyFormatter::format($amount, $this->currency_settings ?? []);
    }

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

    public function viberUrl(): ?string
    {
        return \App\Support\ContactLinkBuilder::viberChatUrl($this->viber_number ?? $this->phone);
    }

    public function telegramUrl(): ?string
    {
        return \App\Support\ContactLinkBuilder::telegramUrl($this->telegram_username);
    }

    // -------------------------------------------------------------------------
    // Theme / Colour scheme helpers
    // -------------------------------------------------------------------------

    /**
     * Named preset → HEX defaults map (kept for backwards compatibility).
     * @deprecated Use ThemeRegistry::allValidIds() for the allow-list or ThemeConfig::fromArray() for normalization.
     *             Kept for backward compatibility with Blade templates that reference preset colors directly.
     */
    public const THEME_PRESETS = [
        'sky'             => ['primary' => '#0ea5e9', 'accent' => '#7c3aed', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
        'midnight'        => ['primary' => '#38bdf8', 'accent' => '#fb923c', 'header_bg' => '#0f172a', 'body_bg' => '#0f172a', 'glow_style' => 'vivid', 'dark_mode' => 'dark'],
        'emerald'         => ['primary' => '#10b981', 'accent' => '#f59e0b', 'header_bg' => '#ffffff', 'body_bg' => '#f0fdf4', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
        'rose'            => ['primary' => '#e11d48', 'accent' => '#f59e0b', 'header_bg' => '#fff1f2', 'body_bg' => '#fff5f6', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
        'violet'          => ['primary' => '#7c3aed', 'accent' => '#10b981', 'header_bg' => '#1e1b4b', 'body_bg' => '#faf5ff', 'glow_style' => 'vivid', 'dark_mode' => 'dark'],
        'marketplace_pro' => ['primary' => '#0ea5e9', 'accent' => '#7c3aed', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
        'retail_trust'    => ['primary' => '#2563eb', 'accent' => '#f59e0b', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'subtle', 'dark_mode' => 'auto'],
        'emerald_fresh'   => ['primary' => '#10b981', 'accent' => '#f59e0b', 'header_bg' => '#ffffff', 'body_bg' => '#f0fdf4', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
        'midnight_tech'   => ['primary' => '#38bdf8', 'accent' => '#fb923c', 'header_bg' => '#0f172a', 'body_bg' => '#0f172a', 'glow_style' => 'vivid', 'dark_mode' => 'dark'],
        'sunset_warm'     => ['primary' => '#e11d48', 'accent' => '#f59e0b', 'header_bg' => '#fff1f2', 'body_bg' => '#fff5f6', 'glow_style' => 'subtle', 'dark_mode' => 'auto'],
        // 'custom' is intentionally absent — custom color stores are handled by per-column overrides
        // in themeColors() and resolved by ThemeConfig::fromArray() at publish time.
    ];

    /**
     * Get the active ThemeManifest instance for this store.
     */
    public function themeManifest(): \App\Themes\ThemeManifest
    {
        return \App\Themes\ThemeRegistry::get($this->theme_preset);
    }

    /**
     * Get the CSS font-family stack for storefront typography.
     */
    public function fontFamilyCss(): string
    {
        $preset = $this->font_preset ?? $this->themeManifest()->defaultFont;
        return \App\Themes\ThemeRegistry::FONT_PRESETS[$preset]['css'] ?? \App\Themes\ThemeRegistry::FONT_PRESETS['outfit']['css'];
    }

    /**
     * Get the Tailwind CSS grid class for product lists.
     */
    public function gridDensityClass(): string
    {
        $density = $this->grid_density ?? $this->themeManifest()->defaultDensity;
        return \App\Themes\ThemeRegistry::GRID_DENSITIES[$density]['class'] ?? \App\Themes\ThemeRegistry::GRID_DENSITIES['compact']['class'];
    }

    /**
     * Resolved theme colours for the active storefront.
     * Preset colors are used as defaults; custom per-column values override them.
     * Returns an array with 'primary', 'accent', 'header_bg', 'body_bg', 'glow_style', 'dark_mode'.
     */
    public function themeColors(): array
    {
        $manifest = $this->themeManifest();
        $defaults = $manifest->colors;

        return [
            'primary'    => $this->theme_primary_color ?: $defaults['primary'],
            'accent'     => $this->theme_accent_color  ?: $defaults['accent'],
            'header_bg'  => $this->theme_header_bg     ?: $defaults['header_bg'],
            'body_bg'    => $this->theme_body_bg       ?: ($defaults['body_bg'] ?? '#f8fafc'),
            'glow_style' => $this->theme_glow_style    ?: ($defaults['glow_style'] ?? 'vivid'),
            'dark_mode'  => $this->theme_dark_mode     ?: ($defaults['dark_mode'] ?? 'auto'),
        ];
    }
}
