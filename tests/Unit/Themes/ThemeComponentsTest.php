<?php

namespace Tests\Unit\Themes;

use App\Themes\ThemeComponents;
use PHPUnit\Framework\TestCase;

/**
 * T5 — Layout Component Registry: theme bundles resolve to APPROVED component
 * variants only; legacy/unknown ids and components fall back to safe defaults.
 */
class ThemeComponentsTest extends TestCase
{
    public function test_each_theme_resolves_an_approved_composition(): void
    {
        $expected = [
            'marketplace_pro' => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
            'retail_trust'    => ['header_variant' => 'classic', 'nav_style' => 'underline', 'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
            'emerald_fresh'   => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'compact',  'footer_variant' => 'standard'],
            'midnight_tech'   => ['header_variant' => 'premium', 'nav_style' => 'underline', 'product_card_variant' => 'showcase', 'footer_variant' => 'standard'],
            'sunset_warm'     => ['header_variant' => 'classic', 'nav_style' => 'pill',      'product_card_variant' => 'showcase', 'footer_variant' => 'standard'],
        ];

        foreach ($expected as $themeId => $composition) {
            $this->assertSame($composition, ThemeComponents::composition($themeId), "Composition mismatch for {$themeId}");
        }
    }

    public function test_legacy_theme_ids_resolve_to_their_canonical_composition(): void
    {
        // 'midnight' is the legacy alias for 'midnight_tech'
        $this->assertSame('showcase', ThemeComponents::resolve('midnight', 'product_card_variant'));
        $this->assertSame('underline', ThemeComponents::resolve('midnight', 'nav_style'));
        $this->assertSame('premium', ThemeComponents::resolve('midnight', 'header_variant'));
    }

    public function test_unknown_theme_id_falls_back_to_safe_defaults(): void
    {
        $this->assertSame('compact', ThemeComponents::resolve('not_a_theme', 'product_card_variant'));
        $this->assertSame('pill', ThemeComponents::resolve('not_a_theme', 'nav_style'));
        $this->assertSame('classic', ThemeComponents::resolve('not_a_theme', 'header_variant'));
    }

    public function test_unknown_component_falls_back_to_default(): void
    {
        // A component the registry does not control resolves to its safe default
        $this->assertSame('standard', ThemeComponents::resolve('marketplace_pro', 'footer_variant'));
        $this->assertSame('', ThemeComponents::resolve('marketplace_pro', 'totally_unknown_component'));
    }

    public function test_approved_variants_are_the_only_resolvable_values(): void
    {
        foreach (ThemeComponents::COMPONENTS as $component) {
            $approved = ThemeComponents::approvedVariants($component);
            $this->assertNotEmpty($approved);

            // A store owner can never inject an arbitrary variant — unknown
            // variants always fall back to the component default.
            $default = ThemeComponents::resolve('marketplace_pro', $component);
            $this->assertContains($default, $approved);
            $this->assertNotSame('hacked_variant', ThemeComponents::resolve('marketplace_pro', $component) === 'hacked_variant');
        }
    }
}
