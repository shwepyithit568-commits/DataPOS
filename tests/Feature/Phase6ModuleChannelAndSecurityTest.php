<?php

namespace Tests\Feature;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Models\AuditLog;
use App\Models\Order;
use App\Models\Store;
use App\Models\StorefrontNavigationItem;
use App\Models\User;
use App\POS\Models\CashierShift;
use App\Rules\SafeNavigationUrlRule;
use App\Services\ModuleBlockerService;
use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase6ModuleChannelAndSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();
    }

    private function createStore(array $attributes = []): Store
    {
        return Store::create(array_merge([
            'name' => 'Security Test Store ' . Str::random(4),
            'slug' => 'security-store-' . Str::lower(Str::random(6)),
            'is_active' => true,
            'business_profile' => 'retail_store',
            'operation_mode' => BusinessProfile::MODE_OMNICHANNEL,
        ], $attributes));
    }

    private function createStoreOwner(Store $store): User
    {
        $user = User::create([
            'name' => 'Store Owner',
            'phone' => '09' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $user->stores()->attach($store->id, [
            'role' => 'store_owner',
            'status' => 'active',
        ]);

        return $user;
    }

    /**
     * Test 1: SafeNavigationUrlRule accepts valid URLs and rejects attack vectors.
     */
    public function test_safe_navigation_url_rule_vectors(): void
    {
        $rule = new SafeNavigationUrlRule();

        // Valid Cases
        $validUrls = [
            '/catalog',
            '/store/abc/about',
            '/contact?subject=help#faq',
            'https://example.com/promo',
            'https://sub.domain.org/path/to/page?id=123',
        ];

        foreach ($validUrls as $url) {
            $validator = Validator::make(['url' => $url], ['url' => $rule]);
            $this->assertTrue($validator->passes(), "Expected '{$url}' to pass SafeNavigationUrlRule.");
        }

        // Invalid / Attack Vectors
        $maliciousUrls = [
            'javascript:alert(1)',
            '  JAVASCRIPT:alert(1) ',
            "javascript\x00:alert(1)",
            'data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==',
            'vbscript:msgbox(1)',
            '//evil.com/phishing',
            '/\\evil.com/phishing',
            '\\evil.com/phishing',
            '/%252f/evil.com',
            '%6a%61%76%61%73%63%72%69%70%74:alert(1)',
            '/about\\attack',
            'https://user:password@evil.com/steal',
            "/valid/path\r\nSet-Cookie:malicious=1",
            "/valid/path\x07bell",
            'ftp://ftp.example.com/file',
            'file:///etc/passwd',
        ];

        foreach ($maliciousUrls as $url) {
            $validator = Validator::make(['url' => $url], ['url' => $rule]);
            $this->assertFalse($validator->passes(), "Expected malicious URL '{$url}' to be rejected by SafeNavigationUrlRule.");
        }
    }

    /**
     * Test 2: Enforces Desktop Max 10 placement limit.
     */
    public function test_storefront_navigation_enforces_desktop_max_10_limit(): void
    {
        $store = $this->createStore();
        $owner = $this->createStoreOwner($store);

        // Seed 10 enabled desktop items
        for ($i = 1; $i <= 10; $i++) {
            StorefrontNavigationItem::create([
                'store_id' => $store->id,
                'menu_key' => 'desktop_item_' . $i,
                'label_my' => 'မီနူး ' . $i,
                'label_en' => 'Menu ' . $i,
                'icon_key' => 'home',
                'destination_type' => 'system',
                'destination_key' => 'home',
                'show_desktop' => true,
                'show_mobile_drawer' => true,
                'show_mobile_bottom' => false,
                'is_enabled' => true,
                'sort_order' => $i * 10,
            ]);
        }

        // Attempting to create an 11th desktop item must fail with 422
        $response = $this->actingAs($owner)->post("/store/{$store->slug}/admin/navigation", [
            'label_my' => 'မီနူး ၁၁',
            'label_en' => 'Menu 11',
            'icon_key' => 'home',
            'destination_type' => 'system',
            'destination_key' => 'home',
            'show_desktop' => true,
            'show_mobile_drawer' => true,
            'show_mobile_bottom' => false,
            'is_enabled' => true,
        ]);

        $response->assertStatus(422);

        // An 11th item that does NOT have show_desktop enabled should succeed
        $validResponse = $this->actingAs($owner)->post("/store/{$store->slug}/admin/navigation", [
            'label_my' => 'မိုဘိုင်း မီနူး',
            'label_en' => 'Mobile Only',
            'icon_key' => 'home',
            'destination_type' => 'system',
            'destination_key' => 'home',
            'show_desktop' => false,
            'show_mobile_drawer' => true,
            'show_mobile_bottom' => false,
            'is_enabled' => true,
        ]);

        $validResponse->assertRedirect();
        $this->assertDatabaseHas('storefront_navigation_items', [
            'store_id' => $store->id,
            'label_en' => 'Mobile Only',
        ]);
    }

    /**
     * Test 3: Enforces Mobile Bottom Max 5 placement limit on toggle.
     */
    public function test_storefront_navigation_enforces_mobile_bottom_max_5_on_toggle(): void
    {
        $store = $this->createStore();
        $owner = $this->createStoreOwner($store);

        // Seed 5 enabled mobile bottom items
        for ($i = 1; $i <= 5; $i++) {
            StorefrontNavigationItem::create([
                'store_id' => $store->id,
                'menu_key' => 'bottom_item_' . $i,
                'label_my' => 'အောက်ခြေ ' . $i,
                'label_en' => 'Bottom ' . $i,
                'icon_key' => 'home',
                'destination_type' => 'system',
                'destination_key' => 'home',
                'show_desktop' => false,
                'show_mobile_drawer' => true,
                'show_mobile_bottom' => true,
                'is_enabled' => true,
                'sort_order' => $i * 10,
            ]);
        }

        // Seed a 6th disabled mobile bottom item
        $sixthItem = StorefrontNavigationItem::create([
            'store_id' => $store->id,
            'menu_key' => 'bottom_item_6',
            'label_my' => 'အောက်ခြေ ၆',
            'label_en' => 'Bottom 6',
            'icon_key' => 'home',
            'destination_type' => 'system',
            'destination_key' => 'home',
            'show_desktop' => false,
            'show_mobile_drawer' => true,
            'show_mobile_bottom' => true,
            'is_enabled' => false,
            'sort_order' => 60,
        ]);

        // Attempting to toggle it on must abort with 422
        $response = $this->actingAs($owner)->post("/store/{$store->slug}/admin/navigation/{$sixthItem->id}/toggle");
        $response->assertStatus(422);

        // Item remains disabled
        $this->assertFalse($sixthItem->fresh()->is_enabled);
    }

    /**
     * Test 4: Blocker service blocks disabling modules when active business records exist.
     */
    public function test_blocker_service_prevents_disabling_module_with_active_records(): void
    {
        $store = $this->createStore();
        $owner = $this->createStoreOwner($store);

        // Seed an active cashier shift
        CashierShift::create([
            'store_id' => $store->id,
            'register_name' => 'Counter 1',
            'cashier_id' => $owner->id,
            'status' => 'open',
            'opened_at' => now(),
            'opening_cash' => 50000,
        ]);

        $blockerService = app(ModuleBlockerService::class);
        $blockers = $blockerService->getBlockersForCapability($store, Capability::OPERATIONS_CASHIER_SHIFTS);

        $this->assertNotEmpty($blockers);
        $this->assertEquals('cashier_shifts', $blockers[0]['domain']);

        // Attempt to toggle cashier shifts off via endpoint
        $response = $this->actingAs($owner)->post("/store/{$store->slug}/admin/settings/modules/toggle", [
            'capability' => Capability::OPERATIONS_CASHIER_SHIFTS,
        ]);

        $response->assertSessionHas('error');
        // Capability should still be active
        $this->assertTrue($store->fresh()->hasCapability(Capability::OPERATIONS_CASHIER_SHIFTS));
    }

    /**
     * Test 5: Modules can be toggled when clean, creating audit records.
     */
    public function test_module_can_be_toggled_cleanly_and_creates_audit_log(): void
    {
        $store = $this->createStore();
        $owner = $this->createStoreOwner($store);

        $response = $this->actingAs($owner)->post("/store/{$store->slug}/admin/settings/modules/toggle", [
            'capability' => Capability::CATALOG_VARIANTS,
            'reason' => 'Do not need variants for simple groceries',
        ]);

        $response->assertSessionHas('success');
        $this->assertFalse($store->fresh()->hasCapability(Capability::CATALOG_VARIANTS));

        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'actor_id' => $owner->id,
            'action' => 'store_module_toggle',
        ]);
    }

    /**
     * Test 6: Sales Channel rules: POS cannot be disabled; Online Ordering depends on Online Store.
     */
    public function test_sales_channel_rules_and_invariants(): void
    {
        $store = $this->createStore([
            'sales_channels' => [
                'pos' => true,
                'online_store' => false,
                'online_ordering' => false,
            ],
        ]);
        $owner = $this->createStoreOwner($store);

        // 1. POS cannot be disabled
        $posResponse = $this->actingAs($owner)->post("/store/{$store->slug}/admin/settings/channels/toggle", [
            'channel' => 'pos',
        ]);
        $posResponse->assertSessionHas('error');
        $this->assertTrue($store->fresh()->hasSalesChannel(Store::CHANNEL_POS));

        // 2. Enabling online_ordering automatically enables online_store
        $orderResponse = $this->actingAs($owner)->post("/store/{$store->slug}/admin/settings/channels/toggle", [
            'channel' => 'online_ordering',
        ]);
        $orderResponse->assertSessionHas('success');

        $refreshed = $store->fresh();
        $this->assertTrue($refreshed->hasSalesChannel(Store::CHANNEL_ONLINE_ORDERING));
        $this->assertTrue($refreshed->hasSalesChannel(Store::CHANNEL_ONLINE_STORE));

        // 3. Blocker prevents disabling online_store when pending orders exist
        Order::create([
            'store_id' => $store->id,
            'order_number' => 'ORD-BLOCK-1',
            'customer_name' => 'Ko Aung',
            'customer_phone' => '0911223344',
            'status' => 'pending_contact',
            'total_amount' => 35000,
        ]);

        $disableResponse = $this->actingAs($owner)->post("/store/{$store->slug}/admin/settings/channels/toggle", [
            'channel' => 'online_store',
        ]);
        $disableResponse->assertSessionHas('error');
        $this->assertTrue($store->fresh()->hasSalesChannel(Store::CHANNEL_ONLINE_STORE));

        // Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'store_id' => $store->id,
            'action' => 'store_channel_toggle',
        ]);
    }

    /**
     * Test 7: Automated translation parity test across Myanmar, English, and Chinese.
     */
    public function test_translation_key_parity_across_all_locales(): void
    {
        $en = require base_path('lang/en/messages.php');
        $my = require base_path('lang/my/messages.php');
        $zh = require base_path('lang/zh_CN/messages.php');

        $missingInMy = array_diff_key($en, $my);
        $missingInZh = array_diff_key($en, $zh);

        $this->assertEmpty($missingInMy, 'Keys missing in lang/my/messages.php: ' . implode(', ', array_keys($missingInMy)));
        $this->assertEmpty($missingInZh, 'Keys missing in lang/zh_CN/messages.php: ' . implode(', ', array_keys($missingInZh)));
        $this->assertSame(count($en), count($my), 'Key count mismatch between EN and MY.');
        $this->assertSame(count($en), count($zh), 'Key count mismatch between EN and ZH.');
    }
}
