<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Batch 3 — localization key parity across en / my / zh_CN.
 *
 * The storefront navigation switched to nav_* keys and the admin sidebar
 * gained accessibility/role keys; every locale must expose the same set so
 * no page falls back to showing raw keys.
 *
 * @see lang/en/messages.php
 * @see lang/my/messages.php
 * @see lang/zh_CN/messages.php
 */
class LocalizationKeysParityTest extends TestCase
{
    private const LOCALES = ['en', 'my', 'zh_CN'];

    private function messagesFor(string $locale): array
    {
        return require lang_path($locale . '/messages.php');
    }

    public function test_all_locales_expose_identical_key_sets(): void
    {
        $enKeys = array_keys($this->messagesFor('en'));
        sort($enKeys);

        foreach (['my', 'zh_CN'] as $locale) {
            $keys = array_keys($this->messagesFor($locale));
            sort($keys);
            $this->assertSame(
                $enKeys,
                $keys,
                "Locale [$locale] key set differs from English."
            );
        }
    }

    public function test_no_locale_contains_duplicate_keys(): void
    {
        foreach (self::LOCALES as $locale) {
            $messages = $this->messagesFor($locale);
            $this->assertCount(
                count($messages),
                array_unique(array_keys($messages)),
                "Locale [$locale] contains duplicate keys."
            );
        }
    }

    public function test_batch3_navigation_and_admin_keys_present_in_all_locales(): void
    {
        $expectedKeys = [
            'nav_home',
            'nav_products',
            'nav_glass_finder',
            'nav_cart',
            'nav_account',
            'admin_navigation',
            'admin_panel',
            'close_menu',
            'open_menu',
            'users',
            'logo',
        ];

        foreach (self::LOCALES as $locale) {
            $messages = $this->messagesFor($locale);
            foreach ($expectedKeys as $key) {
                $this->assertArrayHasKey($key, $messages, "Locale [$locale] missing key [$key].");
                $this->assertNotSame('', $messages[$key], "Locale [$locale] key [$key] is empty.");
            }
        }
    }

    public function test_rebranded_store_name_is_consistent_across_locales(): void
    {
        foreach (self::LOCALES as $locale) {
            $messages = $this->messagesFor($locale);
            $this->assertStringContainsString('DataPOS', $messages['welcome']);
            $this->assertStringContainsString('DataPOS', $messages['register_intro']);
            $this->assertStringNotContainsString('ACDC Mobile', $messages['welcome']);
        }
    }
}
