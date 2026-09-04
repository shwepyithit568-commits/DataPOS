<?php

namespace App\BusinessProfiles;

use App\Capabilities\Capability;

class BusinessProfileRegistry
{
    /**
     * Profile definitions and their default bundled capabilities.
     *
     * @var array<string, array{
     *     name_en: string,
     *     name_mm: string,
     *     description: string,
     *     icon: string,
     *     default_capabilities: list<string>
     * }>
     */
    protected static array $profiles = [
        BusinessProfile::MOBILE_ELECTRONICS => [
            'name_en'     => 'Mobile & Electronics Store',
            'name_mm'     => 'ဖုန်း၊ အပိုပစ္စည်းနှင့် အီလက်ထရောနစ်',
            'description' => 'Specialized for phones, gadgets, CCTV, PC sales & repairs with IMEI/Serial tracking and warranty claims.',
            'icon'        => 'heroicon-o-device-phone-mobile',
            'default_capabilities' => [
                Capability::STOREFRONT_ECOMMERCE,
                Capability::STOREFRONT_ONLINE_ORDERING,
                Capability::STOREFRONT_CUSTOMER_PORTAL,
                Capability::STOREFRONT_GLASS_FINDER,
                Capability::STOREFRONT_BLOG,
                Capability::STOREFRONT_REVIEWS,
                Capability::CATALOG_VARIANTS,
                Capability::CATALOG_CUSTOM_FIELDS,
                Capability::CATALOG_BARCODE_PRINTING,
                Capability::CATALOG_PRICE_WIZARD,
                Capability::INVENTORY_SERIAL_TRACKING,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::INVENTORY_TRANSFERS,
                Capability::SERVICE_REPAIR_JOBS,
                Capability::SERVICE_WARRANTY_TRACKING,
                Capability::SERVICE_SPARE_PARTS,
                Capability::COMMERCE_WHOLESALE,
                Capability::COMMERCE_CUSTOMER_DEBT,
                Capability::COMMERCE_LOYALTY,
                Capability::COMMERCE_SUPPLIER_PAYABLES,
                Capability::OPERATIONS_BRANCHES,
                Capability::OPERATIONS_WAREHOUSES,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::OPERATIONS_ELOAD,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],

        BusinessProfile::GENERAL_RETAIL => [
            'name_en'     => 'General Retail & Mart',
            'name_mm'     => 'အထွေထွေ လက်လီ/လက်ကားနှင့် စတိုးဆိုင်',
            'description' => 'Optimized for high-speed barcode checkout, product variants, wholesale pricing, and shift reconciliation.',
            'icon'        => 'heroicon-o-shopping-bag',
            'default_capabilities' => [
                Capability::STOREFRONT_ECOMMERCE,
                Capability::STOREFRONT_ONLINE_ORDERING,
                Capability::STOREFRONT_CUSTOMER_PORTAL,
                Capability::STOREFRONT_BLOG,
                Capability::STOREFRONT_REVIEWS,
                Capability::CATALOG_VARIANTS,
                Capability::CATALOG_BARCODE_PRINTING,
                Capability::CATALOG_PRICE_WIZARD,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::INVENTORY_TRANSFERS,
                Capability::COMMERCE_WHOLESALE,
                Capability::COMMERCE_CUSTOMER_DEBT,
                Capability::COMMERCE_LOYALTY,
                Capability::COMMERCE_SUPPLIER_PAYABLES,
                Capability::OPERATIONS_BRANCHES,
                Capability::OPERATIONS_WAREHOUSES,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],

        BusinessProfile::REPAIR_SERVICE => [
            'name_en'     => 'Dedicated Repair & Service Center',
            'name_mm'     => 'ပစ္စည်းပြုပြင်ရေးနှင့် ဝန်ဆောင်မှုစင်တာ',
            'description' => 'Focused on service job tickets, technician assignment, spare parts consumption, and customer tracking.',
            'icon'        => 'heroicon-o-wrench-screwdriver',
            'default_capabilities' => [
                Capability::STOREFRONT_ECOMMERCE,
                Capability::STOREFRONT_CUSTOMER_PORTAL,
                Capability::CATALOG_VARIANTS,
                Capability::CATALOG_BARCODE_PRINTING,
                Capability::INVENTORY_SERIAL_TRACKING,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::SERVICE_REPAIR_JOBS,
                Capability::SERVICE_WARRANTY_TRACKING,
                Capability::SERVICE_SPARE_PARTS,
                Capability::COMMERCE_CUSTOMER_DEBT,
                Capability::COMMERCE_SUPPLIER_PAYABLES,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],

        BusinessProfile::PHARMACY => [
            'name_en'     => 'Pharmacy & Healthcare',
            'name_mm'     => 'ဆေးဝါးနှင့် ကျန်းမာရေးသုံးပစ္စည်း',
            'description' => 'Equipped with Batch/Lot tracking, Expiration date alerts, FEFO issuing, and packaging Multi-UOM.',
            'icon'        => 'heroicon-o-heart',
            'default_capabilities' => [
                Capability::STOREFRONT_ECOMMERCE,
                Capability::STOREFRONT_ONLINE_ORDERING,
                Capability::STOREFRONT_CUSTOMER_PORTAL,
                Capability::CATALOG_BARCODE_PRINTING,
                Capability::INVENTORY_BATCH_TRACKING,
                Capability::INVENTORY_EXPIRY_TRACKING,
                Capability::INVENTORY_MULTI_UOM,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::INVENTORY_TRANSFERS,
                Capability::COMMERCE_CUSTOMER_DEBT,
                Capability::COMMERCE_SUPPLIER_PAYABLES,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],

        BusinessProfile::AGRICULTURE => [
            'name_en'     => 'Agriculture & Farm Supplies',
            'name_mm'     => 'စိုက်ပျိုးရေးနှင့် မွေးမြူရေးဆေးပစ္စည်း',
            'description' => 'Tailored for agrochemical batches, multi-unit bulk packing, seasonal wholesale, and credit debt.',
            'icon'        => 'heroicon-o-sparkles',
            'default_capabilities' => [
                Capability::STOREFRONT_ECOMMERCE,
                Capability::STOREFRONT_ONLINE_ORDERING,
                Capability::STOREFRONT_CUSTOMER_PORTAL,
                Capability::INVENTORY_BATCH_TRACKING,
                Capability::INVENTORY_MULTI_UOM,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::INVENTORY_TRANSFERS,
                Capability::COMMERCE_WHOLESALE,
                Capability::COMMERCE_CUSTOMER_DEBT,
                Capability::COMMERCE_SUPPLIER_PAYABLES,
                Capability::OPERATIONS_BRANCHES,
                Capability::OPERATIONS_WAREHOUSES,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],

        BusinessProfile::FOOD_BEVERAGE => [
            'name_en'     => 'Food & Beverage / Cafe',
            'name_mm'     => 'အစားအသောက်၊ ကဖေးနှင့် ဘား',
            'description' => 'Fast touch POS counter, modifiers/add-ons, and daily cash shift auditing.',
            'icon'        => 'heroicon-o-cake',
            'default_capabilities' => [
                Capability::STOREFRONT_ONLINE_ORDERING,
                Capability::CATALOG_VARIANTS,
                Capability::INVENTORY_STOCK_AUDIT,
                Capability::OPERATIONS_CASHIER_SHIFTS,
                Capability::POS_TABLET_TOUCH_MODE,
            ],
        ],
    ];

    /**
     * Get all registered business profiles.
     *
     * @return array<string, array{name_en: string, name_mm: string, description: string, icon: string, default_capabilities: list<string>}>
     */
    public static function all(): array
    {
        return static::$profiles;
    }

    /**
     * Determine if a profile identifier exists.
     */
    public static function has(string $profile): bool
    {
        return isset(static::$profiles[$profile]);
    }

    /**
     * Get profile definition.
     */
    public static function get(string $profile): ?array
    {
        return static::$profiles[$profile] ?? null;
    }

    /**
     * Get the default capabilities list for a profile.
     *
     * @return list<string>
     */
    public static function getDefaultCapabilities(string $profile): array
    {
        return static::$profiles[$profile]['default_capabilities'] ?? static::$profiles[BusinessProfile::MOBILE_ELECTRONICS]['default_capabilities'];
    }

    /**
     * Map legacy or free-text business_type to a standardized BusinessProfile.
     */
    public static function resolveProfile(?string $businessProfile, ?string $businessType = null): string
    {
        if ($businessProfile && static::has($businessProfile)) {
            return $businessProfile;
        }

        $type = strtolower(trim((string) $businessType));

        if (str_contains($type, 'repair')) {
            return BusinessProfile::REPAIR_SERVICE;
        }
        if (str_contains($type, 'pharmacy') || str_contains($type, 'medicine') || str_contains($type, 'health')) {
            return BusinessProfile::PHARMACY;
        }
        if (str_contains($type, 'agri') || str_contains($type, 'farm') || str_contains($type, 'fertilizer')) {
            return BusinessProfile::AGRICULTURE;
        }
        if (str_contains($type, 'food') || str_contains($type, 'restaurant') || str_contains($type, 'bar') || str_contains($type, 'cafe')) {
            return BusinessProfile::FOOD_BEVERAGE;
        }
        if (str_contains($type, 'mobile') || str_contains($type, 'phone') || str_contains($type, 'electronic') || str_contains($type, 'cctv') || str_contains($type, 'computer')) {
            return BusinessProfile::MOBILE_ELECTRONICS;
        }
        if (str_contains($type, 'service')) {
            return BusinessProfile::REPAIR_SERVICE;
        }
        if (str_contains($type, 'retail') || str_contains($type, 'mart') || str_contains($type, 'grocery') || str_contains($type, 'shop')) {
            return BusinessProfile::GENERAL_RETAIL;
        }

        return BusinessProfile::MOBILE_ELECTRONICS;
    }
}
