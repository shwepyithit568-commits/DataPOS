<?php

namespace App\Support;

class ContactLinkBuilder
{
    public static function normalizeMyanmarPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        if ($phone === '') {
            return null;
        }

        $hasPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '09')) {
            return '+95' . substr($digits, 1);
        }

        if (str_starts_with($digits, '959')) {
            return '+' . $digits;
        }

        return $digits;
    }

    public static function viberChatUrl(?string $phone, ?string $draft = null): ?string
    {
        $normalizedPhone = self::normalizeMyanmarPhone($phone);

        if ($normalizedPhone === null) {
            return null;
        }

        // Viber's mobile chat route expects E.164 digits without the leading
        // plus sign. Supplying an encoded "+" can open Viber on iOS but then
        // fail with "Request Unavailable".
        $url = 'viber://chat?number=' . ltrim($normalizedPhone, '+');

        if ($draft !== null && $draft !== '') {
            $url .= '&draft=' . rawurlencode($draft);
        }

        return $url;
    }

    public static function viberIosContactUrl(?string $phone, ?string $draft = null): ?string
    {
        $normalizedPhone = self::normalizeMyanmarPhone($phone);

        if ($normalizedPhone === null) {
            return null;
        }

        // When a draft message matters (e.g. an order), iOS must use the chat
        // route: the contact route cannot carry a draft, so the product name
        // and order details would be lost on iPhone/iPad. The chat route works
        // on iOS as long as the leading '+' is omitted (an encoded '+' is what
        // triggers "Request Unavailable" there).
        if ($draft !== null && $draft !== '') {
            return self::viberChatUrl($phone, $draft);
        }

        // Draft-less contact links keep the contact route: Viber for iOS
        // handles private phone numbers more reliably through it.
        return 'viber://contact?number=%2B' . ltrim($normalizedPhone, '+');
    }

    /**
     * Build the "inquiry" message a shopper sends to ask about a product on
     * Viber. Line-by-line format shared with the client-side builder so the
     * no-JS fallback and the interactive modal produce identical text.
     *
     * @param  array{store_name?:string, product_name:?string, sku:?string, variant_name?:?string, quantity?:int, price?:float, product_url:?string}  $data
     */
    public static function buildProductInquiryMessage(array $data): string
    {
        $store = trim((string) ($data['store_name'] ?? config('app.name')));
        $product = trim((string) ($data['product_name'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? '')) ?: '-';
        $variant = trim((string) ($data['variant_name'] ?? ''));
        $qty = max(1, (int) ($data['quantity'] ?? 1));
        $price = (float) ($data['price'] ?? 0);
        $url = trim((string) ($data['product_url'] ?? ''));

        $lines = [
            'မင်္ဂလာပါ။',
            $store . ' မှာ ဒီပစ္စည်းကို မေးမြန်းချင်ပါတယ်။',
            '',
            'ပစ္စည်း: ' . $product,
            'SKU: ' . $sku,
        ];
        if ($variant !== '') {
            $lines[] = 'ရွေးချယ်မှု: ' . $variant;
        }
        $lines[] = 'အရေအတွက်: ' . $qty;
        if ($price > 0) {
            $lines[] = 'ဈေးနှုန်း: Ks ' . number_format($price);
        }
        if ($url !== '') {
            $lines[] = 'လင့်ခ်: ' . $url;
        }

        return implode("\n", $lines);
    }

    /**
     * Build the full order message the shopper confirms in the Viber order
     * modal before the chat opens. Shared with the client-side builder.
     *
     * @param  array{store_name?:string, product_name:?string, sku:?string, variant_name?:?string, quantity?:int, unit_price?:float, total_price?:float, product_url:?string}  $data
     */
    public static function buildOrderMessage(array $data): string
    {
        $store = trim((string) ($data['store_name'] ?? config('app.name')));
        $product = trim((string) ($data['product_name'] ?? ''));
        $sku = trim((string) ($data['sku'] ?? '')) ?: '-';
        $variant = trim((string) ($data['variant_name'] ?? ''));
        $qty = max(1, (int) ($data['quantity'] ?? 1));
        $unit = (float) ($data['unit_price'] ?? 0);
        $total = (float) ($data['total_price'] ?? ($unit * $qty));
        $url = trim((string) ($data['product_url'] ?? ''));

        $lines = [
            'မင်္ဂလာပါ။',
            $store . ' မှာ အောက်ပါပစ္စည်းကို အော်ဒါတင်ချင်ပါတယ်။',
            '',
            'ပစ္စည်း: ' . $product,
            'SKU: ' . $sku,
        ];
        if ($variant !== '') {
            $lines[] = 'ရွေးချယ်မှု: ' . $variant;
        }
        $lines[] = 'အရေအတွက်: ' . $qty;
        if ($unit > 0) {
            $lines[] = 'တစ်ခုဈေး: Ks ' . number_format($unit);
        }
        if ($total > 0) {
            $lines[] = 'စုစုပေါင်း: Ks ' . number_format($total);
        }
        if ($url !== '') {
            $lines[] = 'ပစ္စည်းလင့်ခ်: ' . $url;
        }

        return implode("\n", $lines);
    }

    public static function telegramUsername(?string $username): ?string
    {
        $username = ltrim(trim((string) $username), '@');

        if ($username === '') {
            return null;
        }

        return preg_replace('/[^A-Za-z0-9_]/', '', $username) ?: null;
    }

    public static function telegramUrl(?string $username, ?string $text = null): ?string
    {
        $username = self::telegramUsername($username);

        if ($username === null) {
            return null;
        }

        $url = 'https://t.me/' . $username;

        if ($text !== null && $text !== '') {
            $url .= '?text=' . rawurlencode($text);
        }

        return $url;
    }
}
