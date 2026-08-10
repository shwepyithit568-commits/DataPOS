<?php

namespace Tests\Unit;

use App\Support\ContactLinkBuilder;
use PHPUnit\Framework\TestCase;

class ContactLinkBuilderTest extends TestCase
{
    /**
     * The existing non-iOS Viber chat URL must remain compatible:
     * E.164 digits without a leading plus sign, as Viber's mobile chat
     * route rejects an encoded "+".
     */
    public function test_viber_chat_url_strips_leading_plus_for_mobile_chat_route(): void
    {
        $this->assertSame(
            'viber://chat?number=959892499955',
            ContactLinkBuilder::viberChatUrl('09892499955')
        );

        // A +95 input normalizes to the same digits and must not double-encode.
        $this->assertSame(
            'viber://chat?number=959892499955',
            ContactLinkBuilder::viberChatUrl('+959892499955')
        );
    }

    public function test_viber_ios_contact_url_is_percent_encoded_with_plus(): void
    {
        // iOS uses the contact route with a percent-encoded E.164 number.
        $this->assertSame(
            'viber://contact?number=%2B959892499955',
            ContactLinkBuilder::viberIosContactUrl('09892499955')
        );

        $this->assertSame(
            'viber://contact?number=%2B959892499955',
            ContactLinkBuilder::viberIosContactUrl('+959892499955')
        );
    }

    public function test_viber_ios_url_with_draft_uses_chat_route_so_message_is_not_lost(): void
    {
        // When an order draft matters, iOS must use the chat route (the
        // contact route cannot carry a draft). The '+' is omitted, which is
        // what makes the chat route work on iOS.
        $this->assertSame(
            'viber://chat?number=959892499955&draft=' . rawurlencode('Hello product'),
            ContactLinkBuilder::viberIosContactUrl('09892499955', 'Hello product')
        );

        $this->assertSame(
            'viber://chat?number=959892499955&draft=' . rawurlencode('Hello product'),
            ContactLinkBuilder::viberIosContactUrl('+959892499955', 'Hello product')
        );
    }

    public function test_viber_ios_url_with_empty_draft_keeps_contact_route(): void
    {
        $this->assertSame(
            'viber://contact?number=%2B959892499955',
            ContactLinkBuilder::viberIosContactUrl('09892499955', '')
        );
    }

    /**
     * Myanmar local (09...) and +95 formats must normalize to the same
     * canonical E.164 number without producing duplicate country prefixes.
     */
    public function test_myanmar_phone_formats_normalize_without_duplicate_prefix(): void
    {
        $this->assertSame('+959892499955', ContactLinkBuilder::normalizeMyanmarPhone('09892499955'));
        $this->assertSame('+959892499955', ContactLinkBuilder::normalizeMyanmarPhone('+959892499955'));
        $this->assertSame('+959892499955', ContactLinkBuilder::normalizeMyanmarPhone('959892499955'));
    }

    public function test_draft_query_is_appended_to_chat_url(): void
    {
        $this->assertSame(
            'viber://chat?number=959892499955&draft=Hello',
            ContactLinkBuilder::viberChatUrl('09892499955', 'Hello')
        );
    }

    /**
     * Empty, null, and non-digit contact values must follow the existing
     * safe behavior of returning null for both URL builders.
     */
    public function test_empty_or_invalid_contact_values_return_null(): void
    {
        foreach (['', null, '   ', 'abcdef', '?!@#'] as $invalid) {
            $this->assertNull(ContactLinkBuilder::viberChatUrl($invalid), "Failed for value: ".var_export($invalid, true));
            $this->assertNull(ContactLinkBuilder::viberIosContactUrl($invalid), "Failed for value: ".var_export($invalid, true));
            $this->assertNull(ContactLinkBuilder::normalizeMyanmarPhone($invalid), "Failed for value: ".var_export($invalid, true));
        }
    }

    // ---- buildProductInquiryMessage / buildOrderMessage tests ----

    public function test_inquiry_message_includes_all_fields(): void
    {
        $msg = ContactLinkBuilder::buildProductInquiryMessage([
            'store_name' => 'DataPOS',
            'product_name' => 'iPhone 15 Pro',
            'sku' => 'IP15-256',
            'variant_name' => '256GB / Blue',
            'quantity' => 2,
            'price' => 2500000,
            'product_url' => 'https://datapos.com/store/datapos-mobile/product/iphone-15-pro',
        ]);

        $this->assertStringContainsString('DataPOS', $msg);
        $this->assertStringContainsString('iPhone 15 Pro', $msg);
        $this->assertStringContainsString('IP15-256', $msg);
        $this->assertStringContainsString('256GB / Blue', $msg);
        $this->assertStringContainsString('အရေအတွက်: 2', $msg);
        $this->assertStringContainsString('Ks 2,500,000', $msg);
        $this->assertStringContainsString('လင့်ခ်:', $msg);
    }

    public function test_inquiry_message_without_variant_omits_variant_line(): void
    {
        $msg = ContactLinkBuilder::buildProductInquiryMessage([
            'store_name' => 'DataPOS',
            'product_name' => 'Cable USB-C',
            'sku' => 'CBL-USBC',
            'variant_name' => null,
            'quantity' => 1,
            'price' => 5000,
        ]);

        $this->assertStringNotContainsString('ရွေးချယ်မှု:', $msg);
        $this->assertStringContainsString('Cable USB-C', $msg);
    }

    public function test_order_message_includes_unit_and_total_price(): void
    {
        $msg = ContactLinkBuilder::buildOrderMessage([
            'store_name' => 'DataPOS',
            'product_name' => 'Screen Protector',
            'sku' => 'SP-001',
            'quantity' => 3,
            'unit_price' => 5000,
            'total_price' => 15000,
        ]);

        $this->assertStringContainsString('တစ်ခုဈေး: Ks 5,000', $msg);
        $this->assertStringContainsString('စုစုပေါင်း: Ks 15,000', $msg);
        $this->assertStringContainsString('အရေအတွက်: 3', $msg);
    }

    public function test_order_message_calculates_total_from_unit_and_qty(): void
    {
        $msg = ContactLinkBuilder::buildOrderMessage([
            'store_name' => 'Shop',
            'product_name' => 'Item',
            'sku' => 'X1',
            'quantity' => 4,
            'unit_price' => 10000,
            // total_price omitted → should auto-calculate
        ]);

        $this->assertStringContainsString('စုစုပေါင်း: Ks 40,000', $msg);
    }

    public function test_missing_sku_shows_dash(): void
    {
        $msg = ContactLinkBuilder::buildProductInquiryMessage([
            'store_name' => 'Shop',
            'product_name' => 'No-SKU Product',
            'sku' => null,
            'quantity' => 1,
        ]);

        $this->assertStringContainsString('SKU: -', $msg);
        $this->assertStringNotContainsString('null', $msg);
        $this->assertStringNotContainsString('undefined', $msg);
    }

    public function test_message_with_burmese_unicode_survives_url_encoding(): void
    {
        $msg = ContactLinkBuilder::buildOrderMessage([
            'store_name' => 'DataPOS',
            'product_name' => 'ဖုန်းမှန် iPhone',
            'sku' => 'GLS-IP15',
            'variant_name' => 'မြင်ကွင်း စတုရန်း',
            'quantity' => 1,
            'unit_price' => 15000,
        ]);

        $url = ContactLinkBuilder::viberChatUrl('09892499955', $msg);

        // URL should contain the encoded Burmese text without corruption
        $this->assertStringStartsWith('viber://chat?number=959892499955&draft=', $url);
        // Decode the draft and verify round-trip
        $draftParam = substr($url, strpos($url, '&draft=') + 7);
        $decoded = rawurldecode($draftParam);
        $this->assertStringContainsString('ဖုန်းမှန် iPhone', $decoded);
        $this->assertStringContainsString('မြင်ကွင်း စတုရန်း', $decoded);
    }

    public function test_special_characters_in_message_do_not_break_url(): void
    {
        $msg = ContactLinkBuilder::buildProductInquiryMessage([
            'store_name' => 'Shop & Co.',
            'product_name' => 'Item (Special) #1 + More',
            'sku' => 'A/B&C#D',
            'quantity' => 1,
            'price' => 1000,
            'product_url' => 'https://example.com/path?q=1&x=2',
        ]);

        $url = ContactLinkBuilder::viberChatUrl('09892499955', $msg);

        // No double-encoding: decode once and verify original content
        $draftParam = substr($url, strpos($url, '&draft=') + 7);
        $decoded = rawurldecode($draftParam);

        $this->assertStringContainsString('Shop & Co.', $decoded);
        $this->assertStringContainsString('Item (Special) #1 + More', $decoded);
        $this->assertStringContainsString('A/B&C#D', $decoded);
        $this->assertStringContainsString('https://example.com/path?q=1&x=2', $decoded);
    }

    public function test_multiline_message_preserves_newlines_in_url(): void
    {
        $msg = ContactLinkBuilder::buildOrderMessage([
            'store_name' => 'Shop',
            'product_name' => 'Test',
            'sku' => 'T1',
            'quantity' => 1,
            'unit_price' => 100,
        ]);

        // Message should have multiple lines
        $this->assertGreaterThan(1, substr_count($msg, "\n"));

        $url = ContactLinkBuilder::viberChatUrl('09892499955', $msg);
        $draftParam = substr($url, strpos($url, '&draft=') + 7);
        $decoded = rawurldecode($draftParam);

        // Newlines survive the round-trip
        $this->assertGreaterThan(1, substr_count($decoded, "\n"));
    }

    public function test_quantity_defaults_to_1_when_invalid(): void
    {
        $msg = ContactLinkBuilder::buildOrderMessage([
            'store_name' => 'Shop',
            'product_name' => 'Item',
            'sku' => 'X',
            'quantity' => 0,
            'unit_price' => 100,
        ]);

        // qty=0 should be clamped to 1
        $this->assertStringContainsString('အရေအတွက်: 1', $msg);
    }
}
