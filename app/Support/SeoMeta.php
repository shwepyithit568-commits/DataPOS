<?php

namespace App\Support;

/**
 * Central SEO helper for storefront pages.
 *
 * Single source of truth for the meta description shown in
 * <meta name="description">, og:description and twitter:description.
 *
 * Fallback priority (see descriptionFor()):
 *   1. product.meta_description (non-empty)
 *   2. plain-text version of product.description
 *   3. safe generic summary built from product name + brand + category
 *   4. store-level default — handled by the storefront layout when
 *      descriptionFor() returns null.
 *
 * Rules (kept deliberately strict):
 *   - HTML tags are stripped (strip_tags) and entities decoded, so no markup
 *     or script content ever reaches a metadata attribute.
 *   - Whitespace (newlines, tabs, runs of spaces) is normalized to single
 *     spaces so metadata stays one clean line.
 *   - Truncation is Unicode-safe (mb_*) with a preferred word boundary and an
 *     ellipsis, capped near the 120–160 character guideline.
 *   - The original description / meta_description columns are never modified.
 */
class SeoMeta
{
    /** Strip tags, decode entities and collapse whitespace to one clean line. */
    public static function clean(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        // Drop script/style blocks entirely (including their text content) so
        // no executable-looking payload survives into a metadata attribute.
        $text = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1\s*>/is', ' ', $value) ?? $value;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\r\n\t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * Unicode-safe truncation for metadata output (never touches the DB).
     *
     * Hard cap at $max characters; when a word boundary exists not too far
     * back inside the window, cut there instead of mid-word. Burmese text
     * (which has no spaces between words) falls back to a clean mb cut, so a
     * multibyte character is never split.
     */
    public static function truncateForMeta(string $text, int $max = 160): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max);
        $lastSpace = mb_strrpos($cut, ' ');

        if ($lastSpace !== false && $lastSpace >= (int) ($max * 0.6)) {
            return mb_substr($cut, 0, $lastSpace) . '…';
        }

        return $cut . '…';
    }

    /**
     * Resolve the SEO description for one product (or null to let the layout
     * fall back to its store-level default).
     */
    public static function descriptionFor(
        ?string $metaDescription,
        ?string $description,
        ?string $productName,
        ?string $brand,
        ?string $category,
        ?string $storeName,
        int $max = 160,
    ): ?string {
        // 1. Explicit meta description.
        $meta = self::clean($metaDescription);
        if ($meta !== '') {
            return self::truncateForMeta($meta, $max);
        }

        // 2. Plain-text version of the rich-text description.
        $plain = self::clean($description);
        if ($plain !== '') {
            return self::truncateForMeta($plain, $max);
        }

        // 3. Safe generic summary: product name + brand + category + store.
        $name = trim((string) $productName);
        if ($name === '') {
            return null;
        }
        $detail = implode(' — ', array_values(array_filter([
            trim((string) $brand),
            trim((string) $category),
        ], fn ($part) => $part !== '')));

        $summary = $detail !== ''
            ? __('messages.seo_fallback_description', ['name' => $name . ' (' . $detail . ')', 'store' => trim((string) $storeName)])
            : __('messages.seo_fallback_description', ['name' => $name, 'store' => trim((string) $storeName)]);

        return self::truncateForMeta($summary, $max);
    }
}
