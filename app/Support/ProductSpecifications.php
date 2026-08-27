<?php

namespace App\Support;

use App\Models\Product;

/**
 * Shared presenter that maps an existing Product into the Specification rows
 * shown on the storefront product page (Description | Specifications tabs) and
 * in the admin product form's read-only Specifications preview.
 *
 * Rules (kept deliberately strict — no invented data):
 *   - Only fields with real, non-empty values are included; empty/null values
 *     are skipped (never "N/A" or a dash).
 *   - Stock status uses the customer-readable Burmese wording
 *     (in_stock → "ပစ္စည်းရှိ", out_of_stock → "ပစ္စည်းပြတ်").
 *   - Prices are intentionally NOT included: the product detail page already
 *     shows them above, and variant prices belong to the variant selector.
 *   - There is no `model` column and no reliable parser exists to extract a
 *     model from the product name/SKU, so Model is never emitted.
 *   - Color / Storage / Size come only from structured variant `attributes`
 *     ({label, value} pairs) — never guessed from names or SKUs.
 *
 * The admin form's Alpine preview (resources/js/app-admin.js → productPreview)
 * mirrors these exact rules for live updates; keep the two in sync.
 */
class ProductSpecifications
{
    /**
     * Single source of truth: structured, non-empty values for one product.
     * Every key is absent (not null) when the product has no value for it, so
     * consumers never have to invent "N/A" placeholders.
     *
     * @return array<string, string>
     */
    public static function structuredFor(Product $product): array
    {
        $data = [];

        if (($brand = trim((string) ($product->brand?->name ?? ''))) !== '') {
            $data['brand'] = $brand;
        }

        if (($category = trim((string) ($product->category?->name ?? ''))) !== '') {
            $data['product_type'] = $category;
        }

        // Main Category = the parent category, only when one exists.
        if ($product->category?->parent && ($parent = trim((string) $product->category->parent->name)) !== '') {
            $data['main_category'] = $parent;
        }

        if (($sku = trim((string) ($product->sku ?? ''))) !== '') {
            $data['sku'] = $sku;
        }

        if (($warranty = trim((string) ($product->warranty ?? ''))) !== '') {
            $data['warranty'] = $warranty;
        }

        // Service/Labor items: how long the service takes (e.g. "30 min", "1 day").
        if (($duration = trim((string) ($product->service_duration ?? ''))) !== '') {
            $data['service_duration'] = $duration;
        }

        // Digital/Code items: how the code is delivered (SMS / Email / Viber…).
        if (($delivery = trim((string) ($product->digital_delivery_method ?? ''))) !== '') {
            $data['delivery_method'] = $delivery;
        }

        if (($stock = self::stockLabel($product->stock_status)) !== null) {
            $data['stock'] = $stock;
        }

        $variants = $product->variants ?? collect();

        // Structured variant attributes (Color / Storage / Size …) grouped by
        // label — only from real {label, value} pairs, never guessed.
        $attributeGroups = [];
        foreach ($variants as $variant) {
            foreach (($variant->attributes ?? []) as $attribute) {
                $label = trim((string) ($attribute['label'] ?? ''));
                $value = trim((string) ($attribute['value'] ?? ''));
                if ($label === '' || $value === '') {
                    continue;
                }
                $attributeGroups[$label][$value] = true;
            }
        }
        foreach ($attributeGroups as $label => $values) {
            $data['attr_' . $label] = implode(', ', array_keys($values));
        }

        $variantNames = $variants->map(fn ($v) => trim((string) $v->name))->filter(fn ($n) => $n !== '')->values();
        if ($variantNames->isNotEmpty()) {
            $data['variant_names'] = $variantNames->implode(', ');
        }

        $variantSkus = $variants->map(fn ($v) => trim((string) $v->sku))->filter(fn ($s) => $s !== '')->values();
        if ($variantSkus->isNotEmpty()) {
            $data['variant_skus'] = $variantSkus->implode(', ');
        }

        return $data;
    }

    /**
     * Display rows (label => value) for the storefront Specifications tab and
     * the admin preview — built from structuredFor() so every consumer shares
     * one mapping.
     *
     * @return array<int, array{label: string, value: string}>
     */
    public static function rowsFor(Product $product): array
    {
        $rows = [];
        $data = self::structuredFor($product);

        $labelMap = [
            'brand' => __('messages.spec_brand'),
            'product_type' => __('messages.spec_product_type'),
            'main_category' => __('messages.spec_main_category'),
            'sku' => __('messages.spec_sku'),
            'warranty' => __('messages.spec_warranty'),
            'service_duration' => __('messages.spec_service_duration'),
            'delivery_method' => __('messages.spec_delivery_method'),
            'stock' => __('messages.spec_stock_status'),
            'variant_names' => __('messages.spec_variant_name'),
            'variant_skus' => __('messages.spec_variant_sku'),
        ];

        foreach ($labelMap as $key => $label) {
            if (isset($data[$key])) {
                $rows[] = ['label' => $label, 'value' => $data[$key]];
            }
        }

        // Variant attribute groups (dynamic labels) come after the fixed rows.
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'attr_')) {
                $rows[] = ['label' => substr($key, 5), 'value' => $value];
            }
        }

        return $rows;
    }

    public static function stockLabel(?string $status): ?string
    {
        // Task-mandated customer-readable wording (Burmese): ပစ္စည်းရှိ / ပစ္စည်းပြတ်.
        return match ($status) {
            'in_stock' => __('messages.spec_stock_in'),
            'out_of_stock' => __('messages.spec_stock_out'),
            default => null,
        };
    }
}
