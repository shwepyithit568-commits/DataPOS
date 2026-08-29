<?php

namespace App\Themes;

/**
 * ThemeConfig — Canonical value object for a store's theme configuration.
 *
 * Rules enforced here (single source of truth):
 *  - Only the 9 known safe tokens are accepted; all other keys are discarded and logged.
 *  - Color values are normalized to lowercase #rrggbb; invalid/missing values
 *    fall back to the theme manifest default.
 *  - Enum fields (glow_style, dark_mode, font_preset, grid_density) are validated
 *    against their respective allow-lists; invalid values fall back to defaults.
 *  - Unknown theme_preset IDs (including legacy aliases) are resolved via
 *    ThemeRegistry before any processing.
 *  - The resulting snapshot is always complete: all 9 fields are present.
 *
 * Usage:
 *   $config = ThemeConfig::fromArray($input);   // normalise + validate
 *   $config->toArray();                          // canonical 9-field snapshot
 *   $config->toRevisionSnapshot();              // + schema_version for revision JSON
 */
final class ThemeConfig
{
    public const SCHEMA_VERSION = 1;

    /** The 9 safe, store-owner-configurable token keys. */
    public const SAFE_KEYS = [
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

    public const GLOW_STYLES  = ['vivid', 'subtle', 'none'];
    public const DARK_MODES   = ['auto', 'light', 'dark'];

    private function __construct(
        public readonly string $themePreset,
        public readonly string $themePrimaryColor,
        public readonly string $themeAccentColor,
        public readonly string $themeHeaderBg,
        public readonly string $themeBodyBg,
        public readonly string $themeGlowStyle,
        public readonly string $themeDarkMode,
        public readonly string $fontPreset,
        public readonly string $gridDensity,
        public readonly string $themeVersion,
    ) {}

    // -------------------------------------------------------------------------
    // Factories
    // -------------------------------------------------------------------------

    /**
     * Build a ThemeConfig from arbitrary user-supplied input.
     * Unknown keys are silently discarded (and logged at debug level).
     *
     * @param array<string, mixed> $input
     */
    public static function fromArray(array $input): self
    {
        // Reject and log unknown keys (safe even without Laravel container)
        $unknownKeys = array_diff(array_keys($input), self::SAFE_KEYS);
        if ($unknownKeys) {
            try {
                \Illuminate\Support\Facades\Log::debug('ThemeConfig: discarding unknown keys', ['keys' => array_values($unknownKeys)]);
            } catch (\Throwable) {
                // No-op in contexts without a Laravel container (e.g., pure unit tests)
            }
        }

        // Strip to known keys only
        $safe = array_intersect_key($input, array_flip(self::SAFE_KEYS));

        // Resolve preset (including legacy aliases)
        $presetRaw = (string) ($safe['theme_preset'] ?? '');
        $preset    = self::resolvePreset($presetRaw);

        // Load manifest defaults for the resolved preset
        $manifest = ThemeRegistry::get($preset);

        return new self(
            themePreset:       $preset,
            themePrimaryColor: self::normalizeColor((string) ($safe['theme_primary_color'] ?? ''), $manifest->primaryColor()),
            themeAccentColor:  self::normalizeColor((string) ($safe['theme_accent_color'] ?? ''),  $manifest->accentColor()),
            themeHeaderBg:     self::normalizeColor((string) ($safe['theme_header_bg'] ?? ''),     $manifest->headerBg()),
            themeBodyBg:       self::normalizeColor((string) ($safe['theme_body_bg'] ?? ''),       $manifest->bodyBg()),
            themeGlowStyle:    self::resolveEnum((string) ($safe['theme_glow_style'] ?? ''),       self::GLOW_STYLES, $manifest->glowStyle()),
            themeDarkMode:     self::resolveEnum((string) ($safe['theme_dark_mode'] ?? ''),        self::DARK_MODES,  $manifest->darkMode()),
            fontPreset:        self::resolveFontPreset((string) ($safe['font_preset'] ?? ''),      $manifest->defaultFont),
            gridDensity:       self::resolveGridDensity((string) ($safe['grid_density'] ?? ''),    $manifest->defaultDensity),
            themeVersion:      $manifest->version,
        );
    }

    /**
     * Build a ThemeConfig from a StorefrontSetting model instance.
     * Useful when creating a snapshot for revision history.
     *
     * @param \App\Models\StorefrontSetting $setting
     */
    public static function fromSetting(\App\Models\StorefrontSetting $setting): self
    {
        return self::fromArray([
            'theme_preset'        => $setting->theme_preset,
            'theme_primary_color' => $setting->theme_primary_color,
            'theme_accent_color'  => $setting->theme_accent_color,
            'theme_header_bg'     => $setting->theme_header_bg,
            'theme_body_bg'       => $setting->theme_body_bg,
            'theme_glow_style'    => $setting->theme_glow_style,
            'theme_dark_mode'     => $setting->theme_dark_mode,
            'font_preset'         => $setting->font_preset,
            'grid_density'        => $setting->grid_density,
        ]);
    }

    // -------------------------------------------------------------------------
    // Output
    // -------------------------------------------------------------------------

    /**
     * Canonical 9-field array suitable for filling StorefrontSetting columns.
     *
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'theme_preset'        => $this->themePreset,
            'theme_primary_color' => $this->themePrimaryColor,
            'theme_accent_color'  => $this->themeAccentColor,
            'theme_header_bg'     => $this->themeHeaderBg,
            'theme_body_bg'       => $this->themeBodyBg,
            'theme_glow_style'    => $this->themeGlowStyle,
            'theme_dark_mode'     => $this->themeDarkMode,
            'font_preset'         => $this->fontPreset,
            'grid_density'        => $this->gridDensity,
        ];
    }

    /**
     * Snapshot array for storing inside store_theme_revisions.theme_config.
     * Includes schema_version (config shape version) and theme_version (theme
     * bundle version) so future migrations can detect and upgrade old rows.
     *
     * @return array<string, mixed>
     */
    public function toRevisionSnapshot(): array
    {
        return array_merge($this->toArray(), [
            'schema_version' => self::SCHEMA_VERSION,
            'theme_version'  => $this->themeVersion,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a preset ID, including legacy alias mapping and unknown-ID fallback.
     */
    private static function resolvePreset(string $id): string
    {
        if ($id === '') {
            return ThemeRegistry::getDefault()->id;
        }

        // ThemeRegistry::get() handles legacy aliases and unknown IDs gracefully.
        $manifest = ThemeRegistry::get($id);
        return $manifest->id;
    }

    /**
     * Normalize a color string to canonical lowercase #rrggbb.
     * Returns the $fallback when the value is empty or invalid.
     */
    private static function normalizeColor(string $value, string $fallback): string
    {
        $v = strtolower(trim($value));

        // Ensure leading '#'
        if ($v !== '' && $v[0] !== '#') {
            $v = '#' . $v;
        }

        // Validate #rrggbb format
        if (preg_match('/^#[0-9a-f]{6}$/', $v)) {
            return $v;
        }

        // Short form #rgb → expand to #rrggbb
        if (preg_match('/^#[0-9a-f]{3}$/', $v)) {
            return '#' . $v[1] . $v[1] . $v[2] . $v[2] . $v[3] . $v[3];
        }

        return strtolower($fallback);
    }

    /**
     * Resolve an enum value against an allow-list; returns $default when invalid/empty.
     *
     * @param list<string> $allowList
     */
    private static function resolveEnum(string $value, array $allowList, string $default): string
    {
        return in_array($value, $allowList, true) ? $value : $default;
    }

    private static function resolveFontPreset(string $value, string $default): string
    {
        $allowed = array_keys(ThemeRegistry::FONT_PRESETS);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function resolveGridDensity(string $value, string $default): string
    {
        $allowed = array_keys(ThemeRegistry::GRID_DENSITIES);
        return in_array($value, $allowed, true) ? $value : $default;
    }
}
