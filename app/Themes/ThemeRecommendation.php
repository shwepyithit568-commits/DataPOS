<?php

namespace App\Themes;

use App\BusinessProfiles\BusinessProfile;

/**
 * ThemeRecommendation — business profile → recommended theme (onboarding only).
 *
 * Per ThemePlan §7 the recommendation is a convenience default, NEVER an
 * authorization rule: it is applied only while provisioning a NEW store /
 * demo store, existing stores are never silently changed, and the store owner
 * may switch to any active theme afterwards.
 */
final class ThemeRecommendation
{
    /** Profile → recommended theme preset (plan §7 onboarding defaults). */
    private const RECOMMENDATIONS = [
        BusinessProfile::MOBILE_ELECTRONICS => 'marketplace_pro',
        BusinessProfile::GENERAL_RETAIL     => 'retail_trust',
        BusinessProfile::REPAIR_SERVICE     => 'retail_trust',
        BusinessProfile::PHARMACY           => 'emerald_fresh',
        BusinessProfile::AGRICULTURE        => 'retail_trust',
        BusinessProfile::FOOD_BEVERAGE      => 'sunset_warm',
    ];

    /** Demo-seeder business_type → profile (demo provisioning uses the same
     *  recommendation; the seeder stores business_type, not business_profile). */
    private const DEMO_BUSINESS_TYPE_TO_PROFILE = [
        'mobile_accessories'      => BusinessProfile::MOBILE_ELECTRONICS,
        'cctv_network_computer'   => BusinessProfile::MOBILE_ELECTRONICS,
        'mobile_sale_service'     => BusinessProfile::MOBILE_ELECTRONICS,
        'pharmacy'                => BusinessProfile::PHARMACY,
        'agriculture_inputs'      => BusinessProfile::AGRICULTURE,
        'restaurant'              => BusinessProfile::FOOD_BEVERAGE,
    ];

    /**
     * Recommended theme preset for a business profile id.
     * Unknown/new profile gets the safe default (marketplace_pro).
     * Only ACTIVE themes are recommended (T7): a hidden/deprecated
     * recommendation falls back to its active replacement, then the default.
     */
    public static function recommendForProfile(string $businessProfile): string
    {
        $themeId = self::RECOMMENDATIONS[$businessProfile] ?? ThemeRegistry::getDefault()->id;

        // Never recommend an id that is not a real, active bundle.
        $themeId = ThemeRegistry::has($themeId) ? $themeId : ThemeRegistry::getDefault()->id;

        $governance = app(\App\Services\ThemeGovernanceService::class);

        if ($governance->effectiveStatus($themeId) !== \App\Models\ThemeGovernance::STATUS_ACTIVE) {
            $replacement = $governance->replacementFor($themeId);
            if ($replacement && $governance->effectiveStatus($replacement) === \App\Models\ThemeGovernance::STATUS_ACTIVE) {
                return $replacement;
            }

            return ThemeRegistry::getDefault()->id;
        }

        return $themeId;
    }

    /**
     * Recommended theme for a demo-seeder business_type value.
     * Unknown business types fall back to the safe default.
     */
    public static function recommendForDemoBusinessType(string $businessType): string
    {
        $profile = self::DEMO_BUSINESS_TYPE_TO_PROFILE[$businessType] ?? null;

        return $profile ? self::recommendForProfile($profile) : ThemeRegistry::getDefault()->id;
    }

    /**
     * All profile ids that have an explicit recommendation.
     *
     * @return list<string>
     */
    public static function knownProfiles(): array
    {
        return array_keys(self::RECOMMENDATIONS);
    }
}
