<?php

namespace App\Capabilities;

class CapabilityRegistry
{
    /**
     * Capability definitions with metadata for grouping, naming, and UI display.
     *
     * @var array<string, array{name_en: string, name_mm: string, group: string, description: string}>
     */
    protected static array $definitions = [
        Capability::STOREFRONT_ECOMMERCE => [
            'name_en'     => 'Online Ecommerce Storefront',
            'name_mm'     => 'အွန်လိုင်း စတိုးမျက်နှာစာ',
            'group'       => 'storefront',
            'description' => 'Enables public web catalog, shopping cart, and online ordering.',
        ],
        Capability::STOREFRONT_ONLINE_ORDERING => [
            'name_en'     => 'Online Direct Ordering',
            'name_mm'     => 'အွန်လိုင်း အော်ဒါတင်စနစ်',
            'group'       => 'storefront',
            'description' => 'Allows customers to submit checkout and order requests online.',
        ],
        Capability::STOREFRONT_CUSTOMER_PORTAL => [
            'name_en'     => 'Customer Account Portal',
            'name_mm'     => 'ဝယ်ယူသူ အကောင့် ပေါ်တယ်',
            'group'       => 'storefront',
            'description' => 'Customer order history, account settings, and saved favorites.',
        ],
        Capability::STOREFRONT_GLASS_FINDER => [
            'name_en'     => 'Tempered Glass / Model Finder',
            'name_mm'     => 'ဖုန်းမှန်မကွဲ / မော်ဒယ် ရှာဖွေမှု',
            'group'       => 'storefront',
            'description' => 'Phone brand/series/model compatibility matrix search tool.',
        ],
        Capability::STOREFRONT_BLOG => [
            'name_en'     => 'Storefront News & Blog',
            'name_mm'     => 'သတင်းနှင့် ဆောင်းပါး စာမျက်နှာ',
            'group'       => 'storefront',
            'description' => 'Public articles and news updates for customer engagement.',
        ],
        Capability::STOREFRONT_REVIEWS => [
            'name_en'     => 'Product Reviews & Ratings',
            'name_mm'     => 'ပစ္စည်း သုံးသပ်ချက်နှင့် အဆင့်သတ်မှတ်မှု',
            'group'       => 'storefront',
            'description' => 'Customer ratings and verified product feedback.',
        ],

        Capability::CATALOG_VARIANTS => [
            'name_en'     => 'Product Variants & Options',
            'name_mm'     => 'ပစ္စည်း အရောင်/ဆိုဒ်/ဗားရှင်းများ',
            'group'       => 'catalog',
            'description' => 'Color, size, storage and customizable SKU variations.',
        ],
        Capability::CATALOG_CUSTOM_FIELDS => [
            'name_en'     => 'Custom Product Attributes',
            'name_mm'     => 'စိတ်ကြိုက် ပစ္စည်းအချက်အလက်များ',
            'group'       => 'catalog',
            'description' => 'Dynamic specifications and attribute key-value pairs.',
        ],
        Capability::CATALOG_BARCODE_PRINTING => [
            'name_en'     => 'Barcode & Price Label Printing',
            'name_mm'     => 'ဘားကုဒ်နှင့် ဈေးနှုန်းတံဆိပ် ရိုက်နှိပ်ခြင်း',
            'group'       => 'catalog',
            'description' => 'Thermal and sticker barcode label generation.',
        ],
        Capability::CATALOG_PRICE_WIZARD => [
            'name_en'     => 'Batch Price Update Wizard',
            'name_mm'     => 'အစုလိုက် ဈေးနှုန်းပြင်ဆင်မှု ဝစ်ဇတ်',
            'group'       => 'catalog',
            'description' => 'Bulk markup and margin calculation adjustments.',
        ],

        Capability::INVENTORY_SERIAL_TRACKING => [
            'name_en'     => 'Serial Number / IMEI Tracking',
            'name_mm'     => 'ဆီးရီးရယ် / IMEI အလုံးလိုက်စစ်ဆေးမှု',
            'group'       => 'inventory',
            'description' => 'Individual unit tracking for phones, laptops, and CCTV devices.',
        ],
        Capability::INVENTORY_BATCH_TRACKING => [
            'name_en'     => 'Batch & Lot Tracking',
            'name_mm'     => 'အသုတ်လိုက် (Batch) မှတ်တမ်းတင်ခြင်း',
            'group'       => 'inventory',
            'description' => 'Batch and lot number tracking for bulk goods and medicines.',
        ],
        Capability::INVENTORY_EXPIRY_TRACKING => [
            'name_en'     => 'Expiry Date & FEFO Management',
            'name_mm'     => 'သက်တမ်းကုန်ရက်နှင့် FEFO စနစ်',
            'group'       => 'inventory',
            'description' => 'Expiration date monitoring and early warning alerts.',
        ],
        Capability::INVENTORY_MULTI_UOM => [
            'name_en'     => 'Multi-Unit of Measurement (UOM)',
            'name_mm'     => 'ယူနစ်အဆင့်ဆင့် (ဖာ/ကတ်/လုံး) သတ်မှတ်မှု',
            'group'       => 'inventory',
            'description' => 'Conversion between packaging units (Carton, Pack, Piece).',
        ],
        Capability::INVENTORY_STOCK_AUDIT => [
            'name_en'     => 'Stock Audit & Reconciliation',
            'name_mm'     => 'ပစ္စည်းလက်ကျန် စစ်ဆေးစာရင်းညှိခြင်း',
            'group'       => 'inventory',
            'description' => 'Periodic inventory cycle counts and discrepancy adjustments.',
        ],
        Capability::INVENTORY_TRANSFERS => [
            'name_en'     => 'Inter-Branch Stock Transfers',
            'name_mm'     => 'ဆိုင်ခွဲ/ဂိုဒေါင်ကြား ပစ္စည်းလွှဲပြောင်းမှု',
            'group'       => 'inventory',
            'description' => 'Stock movements and dispatch verification between locations.',
        ],

        Capability::SERVICE_REPAIR_JOBS => [
            'name_en'     => 'Repair Job Ticket Management',
            'name_mm'     => 'ပစ္စည်းပြင်ဆင်မှု (Service Job) စီမံခန့်ခွဲမှု',
            'group'       => 'service',
            'description' => 'Customer device repair intake, technician logs, and invoicing.',
        ],
        Capability::SERVICE_WARRANTY_TRACKING => [
            'name_en'     => 'Warranty Claims & Tracking',
            'name_mm'     => 'အာမခံ (Warranty) စစ်ဆေးမှတ်တမ်းတင်မှု',
            'group'       => 'service',
            'description' => 'Serial-based warranty coverage validation and claim tracking.',
        ],
        Capability::SERVICE_SPARE_PARTS => [
            'name_en'     => 'Spare Parts & Consumables Ledger',
            'name_mm'     => 'အပိုပစ္စည်းနှင့် ပြုပြင်ရေးပစ္စည်းများ',
            'group'       => 'service',
            'description' => 'Dedicated spare parts tracking deducted on service completion.',
        ],

        Capability::COMMERCE_WHOLESALE => [
            'name_en'     => 'Wholesale Tier Pricing & Partners',
            'name_mm'     => 'လက်ကားဈေးနှုန်းနှင့် မိတ်ဖက်အကောင့်များ',
            'group'       => 'commerce',
            'description' => 'B2B dealer pricing tiers, applications, and bulk discounts.',
        ],
        Capability::COMMERCE_CUSTOMER_DEBT => [
            'name_en'     => 'Customer Receivables & Debt Ledger',
            'name_mm'     => 'ဝယ်သူအကြွေးစာရင်း (Receivables)',
            'group'       => 'commerce',
            'description' => 'Credit limits, payment collection, and customer debt aging.',
        ],
        Capability::COMMERCE_LOYALTY => [
            'name_en'     => 'Customer Loyalty & Points',
            'name_mm'     => 'အသင်းဝင် ကတ်နှင့် အမှတ်စုစနစ်',
            'group'       => 'commerce',
            'description' => 'Points accumulation and tiered loyalty rewards.',
        ],
        Capability::COMMERCE_SUPPLIER_PAYABLES => [
            'name_en'     => 'Supplier Accounts Payable',
            'name_mm'     => 'ကုန်ပေးသွင်းသူ ပေးရန်ကျန်စာရင်း',
            'group'       => 'commerce',
            'description' => 'Vendor credit tracking and scheduled purchase payments.',
        ],

        Capability::OPERATIONS_BRANCHES => [
            'name_en'     => 'Multi-Branch Support',
            'name_mm'     => 'ဆိုင်ခွဲများ စီမံခန့်ခွဲမှု',
            'group'       => 'operations',
            'description' => 'Multiple retail branch locations with localized inventory.',
        ],
        Capability::OPERATIONS_WAREHOUSES => [
            'name_en'     => 'Central Warehouse Management',
            'name_mm'     => 'ဗဟိုဂိုဒေါင်များ စီမံခန့်ခွဲမှု',
            'group'       => 'operations',
            'description' => 'Dedicated stockrooms and regional fulfillment warehouses.',
        ],
        Capability::OPERATIONS_CASHIER_SHIFTS => [
            'name_en'     => 'Cashier Shift Opening & Closing',
            'name_mm'     => 'ကောင်တာ အဆိုင်းဖွင့်/ပိတ်နှင့် နေ့ချုပ်',
            'group'       => 'operations',
            'description' => 'Drawer cash reconciliation and daily cashier shift audits.',
        ],
        Capability::OPERATIONS_ELOAD => [
            'name_en'     => 'Telecom Topup & E-Load Transactions',
            'name_mm'     => 'ဖုန်းဘေလ် / E-Load ဖြည့်သွင်းခြင်း',
            'group'       => 'operations',
            'description' => 'E-load balance tracking, topup sales, and profit margin logging.',
        ],
        Capability::POS_TABLET_TOUCH_MODE => [
            'name_en'     => 'Tablet & Mobile Touch POS Interface',
            'name_mm'     => 'တက်ပလက်/ဖုန်း Touch POS အရောင်းစနစ်',
            'group'       => 'operations',
            'description' => 'Optimized counter UI for touchscreens, iPads, and mobile tablets.',
        ],
    ];

    /**
     * Get all known capability identifiers.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(static::$definitions);
    }

    /**
     * Determine if a capability identifier is registered.
     */
    public static function has(string $capability): bool
    {
        return isset(static::$definitions[$capability]);
    }

    /**
     * Get metadata definition for a capability.
     *
     * @return array{name_en: string, name_mm: string, group: string, description: string}|null
     */
    public static function get(string $capability): ?array
    {
        return static::$definitions[$capability] ?? null;
    }

    /**
     * Get all capability definitions grouped by category.
     *
     * @return array<string, array<string, array{name_en: string, name_mm: string, group: string, description: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (static::$definitions as $key => $def) {
            $grouped[$def['group']][$key] = $def;
        }

        return $grouped;
    }
}
