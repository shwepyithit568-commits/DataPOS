<?php

namespace App\Services;

use App\Models\Store;

class SubscriptionPlanService
{
    /**
     * Subscription Plan Tiers and Quota Definitions.
     */
    public const TIERS = [
        'starter' => [
            'name_en'      => 'Starter Plan',
            'name_mm'      => 'စတင်အသုံးပြုသူ Package',
            'max_products' => 100,
            'max_branches' => 1,
            'features'     => ['POS Counter', 'Basic Catalog', 'Single Branch'],
        ],
        'standard' => [
            'name_en'      => 'Standard Business Plan',
            'name_mm'      => 'စံပြ လုပ်ငန်း Package',
            'max_products' => 1000,
            'max_branches' => 3,
            'features'     => ['POS Counter', 'Storefront E-commerce', 'Wholesale System', '3 Branches', 'Thermal Printing'],
        ],
        'enterprise' => [
            'name_en'      => 'Enterprise Unlimited Plan',
            'name_mm'      => 'ကော်ပိုရိတ် အကန့်အသတ်မဲ့ Package',
            'max_products' => null, // Unlimited
            'max_branches' => null, // Unlimited
            'features'     => ['All Features', 'Unlimited Products', 'Unlimited Branches', 'API Access', 'Priority Support'],
        ],
    ];

    /**
     * Get the max allowed products for a store.
     */
    public static function getMaxProducts(Store $store): ?int
    {
        if ($store->max_products !== null) {
            return $store->max_products;
        }

        $tier = $store->subscription_tier ?? 'standard';
        return self::TIERS[$tier]['max_products'] ?? null;
    }

    /**
     * Get the max allowed branches for a store.
     */
    public static function getMaxBranches(Store $store): ?int
    {
        if ($store->max_branches !== null) {
            return $store->max_branches;
        }

        $tier = $store->subscription_tier ?? 'standard';
        return self::TIERS[$tier]['max_branches'] ?? null;
    }

    /**
     * Check if store can add another product.
     */
    public static function canAddProduct(Store $store): bool
    {
        $max = self::getMaxProducts($store);
        if ($max === null) {
            return true;
        }

        return $store->products()->count() < $max;
    }

    /**
     * Check if store can add another branch.
     */
    public static function canAddBranch(Store $store): bool
    {
        $max = self::getMaxBranches($store);
        if ($max === null) {
            return true;
        }

        return $store->branches()->count() < $max;
    }
}
