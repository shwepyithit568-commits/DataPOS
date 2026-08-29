<?php

namespace Tests\Unit\Themes;

use App\Themes\ThemeConfig;
use App\Themes\ThemeRegistry;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ThemeConfig — the canonical theme normalizer.
 *
 * These tests run without Laravel bootstrapping (extends PHPUnit TestCase,
 * not Illuminate TestCase) so they are fast and isolated.
 */
class ThemeConfigTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Canonical snapshot — all 9 fields always present
    // -------------------------------------------------------------------------

    public function test_fromArray_produces_complete_9_field_snapshot(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#2563eb',
            'theme_accent_color'  => '#f59e0b',
            'theme_header_bg'     => '#ffffff',
            'theme_body_bg'       => '#f8fafc',
            'theme_glow_style'    => 'subtle',
            'theme_dark_mode'     => 'auto',
            'font_preset'         => 'inter',
            'grid_density'        => 'comfortable',
        ]);

        $arr = $config->toArray();

        $this->assertCount(9, $arr);
        foreach (ThemeConfig::SAFE_KEYS as $key) {
            $this->assertArrayHasKey($key, $arr, "Missing key: {$key}");
        }
    }

    // -------------------------------------------------------------------------
    // Unknown keys are discarded — never reach snapshot
    // -------------------------------------------------------------------------

    public function test_unknown_keys_are_discarded_and_do_not_reach_snapshot(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'   => 'retail_trust',
            'evil_script'    => '<script>alert(1)</script>',
            'sql_inject'     => "'; DROP TABLE stores; --",
            'layout_variant' => 'hacked_layout',
            '__proto__'      => 'polluted',
        ]);

        $arr = $config->toArray();

        $this->assertArrayNotHasKey('evil_script', $arr);
        $this->assertArrayNotHasKey('sql_inject', $arr);
        $this->assertArrayNotHasKey('layout_variant', $arr);
        $this->assertArrayNotHasKey('__proto__', $arr);
        $this->assertCount(9, $arr);
    }

    // -------------------------------------------------------------------------
    // Color normalization
    // -------------------------------------------------------------------------

    public function test_uppercase_color_is_normalized_to_lowercase(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#AABBCC',
            'theme_accent_color'  => '#FF0099',
        ]);

        $this->assertSame('#aabbcc', $config->toArray()['theme_primary_color']);
        $this->assertSame('#ff0099', $config->toArray()['theme_accent_color']);
    }

    public function test_short_hex_color_is_expanded_to_6_digits(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => '#abc',
        ]);

        $this->assertSame('#aabbcc', $config->toArray()['theme_primary_color']);
    }

    public function test_invalid_color_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'        => 'retail_trust',
            'theme_primary_color' => 'not-a-color',
            'theme_accent_color'  => '#GGGGGG',
        ]);

        $manifest = ThemeRegistry::get('retail_trust');
        // Falls back to the manifest's own primary/accent color
        $this->assertSame(strtolower($manifest->primaryColor()), $config->toArray()['theme_primary_color']);
        $this->assertSame(strtolower($manifest->accentColor()), $config->toArray()['theme_accent_color']);
    }

    public function test_empty_color_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'        => 'emerald_fresh',
            'theme_primary_color' => '',
        ]);

        $manifest = ThemeRegistry::get('emerald_fresh');
        $this->assertSame(strtolower($manifest->primaryColor()), $config->toArray()['theme_primary_color']);
    }

    // -------------------------------------------------------------------------
    // Enum validation — glow_style, dark_mode
    // -------------------------------------------------------------------------

    public function test_invalid_glow_style_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'     => 'retail_trust',
            'theme_glow_style' => 'explode',
        ]);

        $manifest = ThemeRegistry::get('retail_trust');
        $this->assertSame($manifest->glowStyle(), $config->toArray()['theme_glow_style']);
    }

    public function test_invalid_dark_mode_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset'  => 'midnight_tech',
            'theme_dark_mode' => 'ultra_dark_mode',
        ]);

        $manifest = ThemeRegistry::get('midnight_tech');
        $this->assertSame($manifest->darkMode(), $config->toArray()['theme_dark_mode']);
    }

    public function test_valid_enum_values_are_accepted(): void
    {
        foreach (ThemeConfig::GLOW_STYLES as $glow) {
            $config = ThemeConfig::fromArray(['theme_glow_style' => $glow]);
            $this->assertSame($glow, $config->toArray()['theme_glow_style']);
        }

        foreach (ThemeConfig::DARK_MODES as $mode) {
            $config = ThemeConfig::fromArray(['theme_dark_mode' => $mode]);
            $this->assertSame($mode, $config->toArray()['theme_dark_mode']);
        }
    }

    // -------------------------------------------------------------------------
    // Font preset and grid density validation
    // -------------------------------------------------------------------------

    public function test_invalid_font_preset_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset' => 'retail_trust',
            'font_preset'  => 'comic_sans_haha',
        ]);

        $manifest = ThemeRegistry::get('retail_trust');
        $this->assertSame($manifest->defaultFont, $config->toArray()['font_preset']);
    }

    public function test_invalid_grid_density_falls_back_to_manifest_default(): void
    {
        $config = ThemeConfig::fromArray([
            'theme_preset' => 'marketplace_pro',
            'grid_density' => 'ultra_wide',
        ]);

        $manifest = ThemeRegistry::get('marketplace_pro');
        $this->assertSame($manifest->defaultDensity, $config->toArray()['grid_density']);
    }

    // -------------------------------------------------------------------------
    // Legacy preset ID resolution
    // -------------------------------------------------------------------------

    public function test_legacy_preset_id_is_resolved_to_canonical_id(): void
    {
        $legacyMap = [
            'sky'      => 'marketplace_pro',
            'midnight' => 'midnight_tech',
            'emerald'  => 'emerald_fresh',
            'rose'     => 'sunset_warm',
            'violet'   => 'marketplace_pro',
        ];

        foreach ($legacyMap as $legacyId => $expectedId) {
            $config = ThemeConfig::fromArray(['theme_preset' => $legacyId]);
            $this->assertSame($expectedId, $config->toArray()['theme_preset'], "Legacy '{$legacyId}' should resolve to '{$expectedId}'");
        }
    }

    public function test_unknown_preset_id_falls_back_to_default(): void
    {
        $config = ThemeConfig::fromArray(['theme_preset' => 'non_existent_theme_xyz']);

        $defaultId = ThemeRegistry::getDefault()->id;
        $this->assertSame($defaultId, $config->toArray()['theme_preset']);
    }

    public function test_empty_preset_falls_back_to_default(): void
    {
        $config = ThemeConfig::fromArray(['theme_preset' => '']);

        $defaultId = ThemeRegistry::getDefault()->id;
        $this->assertSame($defaultId, $config->toArray()['theme_preset']);
    }

    // -------------------------------------------------------------------------
    // Missing fields — manifest defaults fill in
    // -------------------------------------------------------------------------

    public function test_missing_fields_are_filled_from_manifest_defaults(): void
    {
        // Only pass the preset — all other fields should come from the manifest
        $config = ThemeConfig::fromArray(['theme_preset' => 'emerald_fresh']);
        $arr    = $config->toArray();
        $manifest = ThemeRegistry::get('emerald_fresh');

        $this->assertSame(strtolower($manifest->primaryColor()), $arr['theme_primary_color']);
        $this->assertSame(strtolower($manifest->accentColor()),  $arr['theme_accent_color']);
        $this->assertSame(strtolower($manifest->headerBg()),     $arr['theme_header_bg']);
        $this->assertSame($manifest->defaultFont,                $arr['font_preset']);
        $this->assertSame($manifest->defaultDensity,             $arr['grid_density']);
    }

    public function test_fromArray_with_completely_empty_input_returns_defaults(): void
    {
        $config = ThemeConfig::fromArray([]);
        $arr    = $config->toArray();

        $this->assertCount(9, $arr);
        $this->assertSame(ThemeRegistry::getDefault()->id, $arr['theme_preset']);
    }

    // -------------------------------------------------------------------------
    // Revision snapshot — schema_version + theme_version included
    // -------------------------------------------------------------------------

    public function test_toRevisionSnapshot_includes_schema_and_theme_version(): void
    {
        $config   = ThemeConfig::fromArray(['theme_preset' => 'retail_trust']);
        $snapshot = $config->toRevisionSnapshot();

        $this->assertArrayHasKey('schema_version', $snapshot);
        $this->assertSame(ThemeConfig::SCHEMA_VERSION, $snapshot['schema_version']);

        $this->assertArrayHasKey('theme_version', $snapshot);
        $this->assertSame(ThemeRegistry::get('retail_trust')->version, $snapshot['theme_version']);

        // Should contain all 9 safe keys + schema_version + theme_version
        $this->assertCount(11, $snapshot);
    }

    public function test_toArray_does_not_include_schema_version(): void
    {
        $config = ThemeConfig::fromArray(['theme_preset' => 'retail_trust']);

        $this->assertArrayNotHasKey('schema_version', $config->toArray());
    }

    // -------------------------------------------------------------------------
    // Determinism — same input always produces same output
    // -------------------------------------------------------------------------

    public function test_same_input_always_produces_same_output(): void
    {
        $input = [
            'theme_preset'        => 'midnight_tech',
            'theme_primary_color' => '#38BDF8',
            'theme_glow_style'    => 'vivid',
            'unknown_key'         => 'hacked',
        ];

        $first  = ThemeConfig::fromArray($input)->toArray();
        $second = ThemeConfig::fromArray($input)->toArray();

        $this->assertSame($first, $second);
    }
}
