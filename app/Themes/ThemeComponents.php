<?php

namespace App\Themes;

/**
 * ThemeComponents — approved layout-component mapping per theme bundle.
 *
 * The ThemeBundle defines which component VARIANTS a store may use
 * (platform-controlled tokens, see ThemePlan §6.2). A store owner can never
 * request an arbitrary variant id — only the approved mapping below resolves;
 * anything unknown falls back to the safe default. This keeps every theme a
 * curated, tested composition while the shared data contracts (ViewModels /
 * prepared data) stay identical across variants — no theme-specific queries.
 */
final class ThemeComponents
{
    /** The layout components the registry controls. */
    public const COMPONENTS = [
        'header_variant',
        'nav_style',
        'product_card_variant',
        'footer_variant',
    ];

    /** Safe defaults when a theme id is unknown / has no entry. */
    private const DEFAULTS = [
        'header_variant'       => 'classic',
        'nav_style'            => 'pill',
        'product_card_variant' => 'compact',
        'footer_variant'       => 'standard',
    ];

    /** Approved variant ids per component (the only values that can resolve). */
    private const APPROVED = [
        'header_variant'       => ['classic', 'premium'],
        'nav_style'            => ['pill', 'underline'],
        'product_card_variant' => ['compact', 'showcase'],
        'footer_variant'       => ['standard'],
    ];

    /**
     * theme_id => approved variant composition.
     * Business-profile recommendations never override this — it is the
     * platform-controlled bundle definition (ThemePlan §7 / §6.2).
     */
    private const MAPPING = [
        'marketplace_pro' => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
        'retail_trust'    => ['header_variant' => 'classic', 'nav_style' => 'underline', 'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
        'emerald_fresh'   => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
        'midnight_tech'   => ['header_variant' => 'premium', 'nav_style' => 'underline', 'product_card_variant' => 'showcase', 'footer_variant' => 'standard'],
        'sunset_warm'     => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'showcase', 'footer_variant' => 'standard'],
    ];

    /**
     * Resolve the approved variant for a theme + component.
     * Legacy/unknown theme ids are canonicalized via ThemeRegistry first;
     * unknown components or variants fall back to the safe default so an
     * unregistered theme can never request an unbuilt layout.
     */
    public static function resolve(string $themeId, string $component): string
    {
        $canonical = ThemeRegistry::get($themeId)->id;

        $default = self::DEFAULTS[$component] ?? '';
        $variant = self::MAPPING[$canonical][$component] ?? $default;

        return in_array($variant, self::APPROVED[$component] ?? [], true) ? $variant : $default;
    }

    /**
     * The approved variant ids for a component (for docs/tests/UI hints).
     *
     * @return list<string>
     */
    public static function approvedVariants(string $component): array
    {
        return self::APPROVED[$component] ?? [];
    }

    /**
     * The full approved composition for a canonical theme id.
     *
     * @return array<string, string>
     */
    public static function composition(string $themeId): array
    {
        $canonical = ThemeRegistry::get($themeId)->id;

        $out = [];
        foreach (self::COMPONENTS as $component) {
            $out[$component] = self::resolve($canonical, $component);
        }

        return $out;
    }
}
