<?php

namespace App\Capabilities;

/**
 * Single-Codebase Capability Identifiers.
 *
 * Capabilities represent distinct, modular functional modules or features
 * that can be enabled or disabled based on a store's BusinessProfile and OperationMode,
 * or fine-tuned via custom capabilities override.
 */
final class Capability
{
    // Storefront & Online Sales
    public const STOREFRONT_ECOMMERCE        = 'storefront.ecommerce';
    public const STOREFRONT_ONLINE_ORDERING  = 'storefront.online_ordering';
    public const STOREFRONT_CUSTOMER_PORTAL  = 'storefront.customer_portal';
    public const STOREFRONT_GLASS_FINDER     = 'storefront.glass_finder';
    public const STOREFRONT_BLOG             = 'storefront.blog';
    public const STOREFRONT_REVIEWS          = 'storefront.reviews';

    // Catalog & Products
    public const CATALOG_VARIANTS            = 'catalog.variants';
    public const CATALOG_CUSTOM_FIELDS       = 'catalog.custom_fields';
    public const CATALOG_BARCODE_PRINTING    = 'catalog.barcode_printing';
    public const CATALOG_PRICE_WIZARD        = 'catalog.price_wizard';

    // Inventory & Logistics
    public const INVENTORY_SERIAL_TRACKING   = 'inventory.serial_tracking';
    public const INVENTORY_BATCH_TRACKING    = 'inventory.batch_tracking';
    public const INVENTORY_EXPIRY_TRACKING   = 'inventory.expiry_tracking';
    public const INVENTORY_MULTI_UOM         = 'inventory.multi_uom';
    public const INVENTORY_STOCK_AUDIT       = 'inventory.stock_audit';
    public const INVENTORY_TRANSFERS         = 'inventory.transfers';

    // Service & Repairs
    public const SERVICE_REPAIR_JOBS         = 'service.repair_jobs';
    public const SERVICE_WARRANTY_TRACKING   = 'service.warranty_tracking';
    public const SERVICE_SPARE_PARTS         = 'service.spare_parts';

    // Commerce & Customers
    public const COMMERCE_WHOLESALE          = 'commerce.wholesale_pricing';
    public const COMMERCE_CUSTOMER_DEBT      = 'commerce.customer_debt';
    public const COMMERCE_LOYALTY            = 'commerce.loyalty_points';
    public const COMMERCE_SUPPLIER_PAYABLES  = 'commerce.supplier_payables';

    // Operations & POS
    public const OPERATIONS_BRANCHES         = 'operations.branches';
    public const OPERATIONS_WAREHOUSES       = 'operations.warehouses';
    public const OPERATIONS_CASHIER_SHIFTS   = 'operations.cashier_shifts';
    public const OPERATIONS_ELOAD            = 'operations.eload';
    public const POS_TABLET_TOUCH_MODE       = 'pos.tablet_touch_mode';
}
