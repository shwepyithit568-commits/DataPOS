<?php

namespace App\BusinessProfiles;

/**
 * Single-Codebase Business Profile and Operation Mode Identifiers.
 */
final class BusinessProfile
{
    // Business Profiles
    public const MOBILE_ELECTRONICS = 'mobile_electronics';
    public const GENERAL_RETAIL     = 'general_retail';
    public const REPAIR_SERVICE     = 'repair_service';
    public const PHARMACY           = 'pharmacy';
    public const AGRICULTURE        = 'agriculture';
    public const FOOD_BEVERAGE      = 'food_beverage';

    // Operation Modes
    public const MODE_OMNICHANNEL   = 'omnichannel';   // POS + Online Web Storefront + Online Ordering
    public const MODE_POS_ONLY      = 'pos_only';      // In-store POS Counter only (no public ecommerce)
    public const MODE_CATALOG_ONLY  = 'catalog_only';  // In-store POS + Online Storefront Catalog (no online ordering)
    public const MODE_CUSTOM        = 'custom';        // Explicit channel selection
}
