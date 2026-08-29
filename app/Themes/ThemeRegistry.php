<?php

namespace App\Themes;

class ThemeRegistry
{
    /**
     * Curated Theme Definitions.
     *
     * @var array<string, array{
     *     name_en: string,
     *     name_mm: string,
     *     description: string,
     *     colors: array<string, string>,
     *     default_font: string,
     *     default_density: string,
     *     version: string,
     *     status: string,
     *     replacement_id: string|null
     * }>
     */
    protected static array $themes = [
        'marketplace_pro' => [
            'name_en'         => 'Marketplace Pro',
            'name_mm'         => 'မားကတ်ပလေ့စ် ပရို (စုံလင်သော ကတ်တလောက်)',
            'description'     => 'Designed for electronics, phones, and multi-category retailers with vibrant modern accents.',
            'colors'          => ['primary' => '#0ea5e9', 'accent' => '#7c3aed', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
            'default_font'    => 'outfit',
            'default_density' => 'compact',
            'version'         => '1',
            'status'          => 'active',
            'replacement_id'  => null,
        ],
        'retail_trust' => [
            'name_en'         => 'Retail Trust',
            'name_mm'         => 'ရီတေးလ် ထရပ်စ် (သန့်ရှင်းရိုးရှင်းသော လက်လီ)',
            'description'     => 'Clean and structured high-contrast layout optimized for general marts, groceries, and daily retail.',
            'colors'          => ['primary' => '#2563eb', 'accent' => '#f59e0b', 'header_bg' => '#ffffff', 'body_bg' => '#f8fafc', 'glow_style' => 'subtle', 'dark_mode' => 'auto'],
            'default_font'    => 'inter',
            'default_density' => 'comfortable',
            'version'         => '1',
            'status'          => 'active',
            'replacement_id'  => null,
        ],
        'emerald_fresh' => [
            'name_en'         => 'Emerald Fresh',
            'name_mm'         => 'အမ်မရယ် ဖရက်ရှ် (ဆေးဝါးနှင့် ကျန်းမာရေး)',
            'description'     => 'Trustworthy natural green and gold palette ideal for pharmacies, healthcare, and cosmetics.',
            'colors'          => ['primary' => '#10b981', 'accent' => '#f59e0b', 'header_bg' => '#ffffff', 'body_bg' => '#f0fdf4', 'glow_style' => 'vivid', 'dark_mode' => 'auto'],
            'default_font'    => 'inter',
            'default_density' => 'compact',
            'version'         => '1',
            'status'          => 'active',
            'replacement_id'  => null,
        ],
        'midnight_tech' => [
            'name_en'         => 'Midnight Tech',
            'name_mm'         => 'မစ်နိုက် တက်ခ် (ပရီမီယံ အမှောင်)',
            'description'     => 'Sleek dark-themed aesthetic with cyan and orange highlights for gaming, computer, and tech shops.',
            'colors'          => ['primary' => '#38bdf8', 'accent' => '#fb923c', 'header_bg' => '#0f172a', 'body_bg' => '#0f172a', 'glow_style' => 'vivid', 'dark_mode' => 'dark'],
            'default_font'    => 'outfit',
            'default_density' => 'compact',
            'version'         => '1',
            'status'          => 'active',
            'replacement_id'  => null,
        ],
        'sunset_warm' => [
            'name_en'         => 'Sunset Warm',
            'name_mm'         => 'ဆန်းဆက် ဝမ်းမ် (ဖက်ရှင်နှင့် လူသုံးကုန်)',
            'description'     => 'Warm rose and amber tones suitable for boutiques, lifestyle accessories, and apparel.',
            'colors'          => ['primary' => '#e11d48', 'accent' => '#f59e0b', 'header_bg' => '#fff1f2', 'body_bg' => '#fff5f6', 'glow_style' => 'subtle', 'dark_mode' => 'auto'],
            'default_font'    => 'outfit',
            'default_density' => 'comfortable',
            'version'         => '1',
            'status'          => 'active',
            'replacement_id'  => null,
        ],
    ];

    /**
     * Map legacy preset names to new theme IDs for backward compatibility.
     */
    protected static array $legacyMap = [
        'sky'      => 'marketplace_pro',
        'midnight' => 'midnight_tech',
        'emerald'  => 'emerald_fresh',
        'rose'     => 'sunset_warm',
        'violet'   => 'marketplace_pro',
        'custom'   => 'marketplace_pro',
    ];

    /**
     * Font presets definition.
     */
    public const FONT_PRESETS = [
        'outfit'     => ['name' => 'Outfit & Pyidaungsu (Modern)', 'css' => "'Outfit', 'Pyidaungsu', system-ui, sans-serif"],
        'inter'      => ['name' => 'Inter & Padauk (Clean Retail)', 'css' => "'Inter', 'Padauk', system-ui, sans-serif"],
        'pyidaungsu' => ['name' => 'Pyidaungsu Standard (Myanmar Official)', 'css' => "'Pyidaungsu', system-ui, sans-serif"],
        'padauk'     => ['name' => 'Padauk Standard (Readable)', 'css' => "'Padauk', system-ui, sans-serif"],
        'system'     => ['name' => 'System Native', 'css' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif"],
    ];

    /**
     * Grid density presets definition.
     */
    public const GRID_DENSITIES = [
        'compact'     => ['name' => 'Compact (Maximum Products)', 'class' => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2.5 sm:gap-3.5'],
        'comfortable' => ['name' => 'Comfortable (Large Showcase)', 'class' => 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6'],
    ];

    /**
     * @return array<string, ThemeManifest>
     */
    public static function all(): array
    {
        $manifests = [];
        foreach (self::$themes as $id => $data) {
            $manifests[$id] = new ThemeManifest(
                id: $id,
                nameEn: $data['name_en'],
                nameMm: $data['name_mm'],
                description: $data['description'],
                colors: $data['colors'],
                defaultFont: $data['default_font'],
                defaultDensity: $data['default_density'],
                version: $data['version'] ?? '1',
                status: $data['status'] ?? 'active',
                replacementId: $data['replacement_id'] ?? null
            );
        }
        return $manifests;
    }

    public static function get(?string $id): ThemeManifest
    {
        if (! $id) {
            return self::getDefault();
        }

        // Check if legacy name passed
        $resolvedId = self::$legacyMap[$id] ?? $id;

        if (isset(self::$themes[$resolvedId])) {
            $data = self::$themes[$resolvedId];
            return new ThemeManifest(
                id: $resolvedId,
                nameEn: $data['name_en'],
                nameMm: $data['name_mm'],
                description: $data['description'],
                colors: $data['colors'],
                defaultFont: $data['default_font'],
                defaultDensity: $data['default_density'],
                version: $data['version'] ?? '1',
                status: $data['status'] ?? 'active',
                replacementId: $data['replacement_id'] ?? null
            );
        }

        return self::getDefault();
    }

    public static function getDefault(): ThemeManifest
    {
        $data = self::$themes['marketplace_pro'];
        return new ThemeManifest(
            id: 'marketplace_pro',
            nameEn: $data['name_en'],
            nameMm: $data['name_mm'],
            description: $data['description'],
            colors: $data['colors'],
            defaultFont: $data['default_font'],
            defaultDensity: $data['default_density'],
            version: $data['version'] ?? '1'
        );
    }

    public static function has(string $id): bool
    {
        $resolvedId = self::$legacyMap[$id] ?? $id;
        return isset(self::$themes[$resolvedId]);
    }

    /**
     * @return list<string>
     */
    public static function ids(): array
    {
        return array_keys(self::$themes);
    }

    /**
     * Expose the legacy-alias map so external code (Blade, ThemeConfig DTO)
     * can reuse the same mapping without duplication.
     *
     * @return array<string, string>
     */
    public static function legacyMap(): array
    {
        return self::$legacyMap;
    }

    /**
     * All valid preset IDs: current IDs + legacy aliases.
     * Use this as the full allow-list for form/validation purposes.
     *
     * @return list<string>
     */
    public static function allValidIds(): array
    {
        return array_unique(array_merge(
            array_keys(self::$themes),
            array_keys(self::$legacyMap),
        ));
    }
}
