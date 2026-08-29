<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductUnit;

class UnitConversionService
{
    /**
     * Convert quantity from specified unit to base units.
     *
     * Example: 2 Boxes (conversion_factor = 10) = 20 Base Units
     */
    public static function convertToBaseQuantity(ProductUnit $unit, float $quantity): float
    {
        return round($quantity * (float) $unit->conversion_factor, 4);
    }

    /**
     * Convert quantity from base units to specified packaging unit.
     *
     * Example: 20 Base Units / (conversion_factor = 10) = 2 Boxes
     */
    public static function convertFromBaseQuantity(ProductUnit $unit, float $baseQuantity): float
    {
        $factor = (float) $unit->conversion_factor;
        if ($factor <= 0) {
            return $baseQuantity;
        }

        return round($baseQuantity / $factor, 4);
    }

    /**
     * Get effective retail price for a unit.
     */
    public static function getUnitRetailPrice(Product $product, ProductUnit $unit): float
    {
        if ($unit->retail_price !== null && (float) $unit->retail_price > 0) {
            return (float) $unit->retail_price;
        }

        $basePrice = (float) $product->retail_price;
        return round($basePrice * (float) $unit->conversion_factor, 2);
    }

    /**
     * Get effective wholesale price for a unit.
     */
    public static function getUnitWholesalePrice(Product $product, ProductUnit $unit): float
    {
        if ($unit->wholesale_price !== null && (float) $unit->wholesale_price > 0) {
            return (float) $unit->wholesale_price;
        }

        $basePrice = (float) $product->wholesale_price;
        return round($basePrice * (float) $unit->conversion_factor, 2);
    }
}
