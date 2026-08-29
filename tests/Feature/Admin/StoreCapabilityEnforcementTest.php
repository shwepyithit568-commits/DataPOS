<?php

namespace Tests\Feature\Admin;

use App\BusinessProfiles\BusinessProfile;
use App\BusinessProfiles\BusinessProfileRegistry;
use App\Capabilities\Capability;
use App\Capabilities\CapabilityRegistry;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreCapabilityEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function createTestStore(array $attributes = []): Store
    {
        $defaults = [
            'name'             => 'Test Store',
            'slug'             => 'test-store-' . uniqid(),
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'operation_mode'   => BusinessProfile::MODE_OMNICHANNEL,
            'is_active'        => true,
        ];

        $store = Store::create(array_merge($defaults, $attributes));
        $store->setting()->create([
            'store_name'       => $store->name,
            'default_language' => 'en',
        ]);

        return $store;
    }

    public function test_capability_registry_contains_defined_capabilities(): void
    {
        $all = CapabilityRegistry::all();
        $this->assertContains(Capability::STOREFRONT_ECOMMERCE, $all);
        $this->assertContains(Capability::SERVICE_REPAIR_JOBS, $all);
        $this->assertContains(Capability::INVENTORY_SERIAL_TRACKING, $all);
        $this->assertContains(Capability::COMMERCE_CUSTOMER_DEBT, $all);
        $this->assertContains(Capability::OPERATIONS_BRANCHES, $all);
    }

    public function test_business_profile_registry_resolves_legacy_business_types(): void
    {
        $this->assertEquals(BusinessProfile::MOBILE_ELECTRONICS, BusinessProfileRegistry::resolveProfile(null, 'Mobile & Accessories'));
        $this->assertEquals(BusinessProfile::PHARMACY, BusinessProfileRegistry::resolveProfile(null, 'Pharmacy & Healthcare'));
        $this->assertEquals(BusinessProfile::AGRICULTURE, BusinessProfileRegistry::resolveProfile(null, 'Agricultural Farm Supplies'));
        $this->assertEquals(BusinessProfile::FOOD_BEVERAGE, BusinessProfileRegistry::resolveProfile(null, 'Restaurant & Food Bar'));
        $this->assertEquals(BusinessProfile::REPAIR_SERVICE, BusinessProfileRegistry::resolveProfile(null, 'Phone Repair Service Center'));
        $this->assertEquals(BusinessProfile::GENERAL_RETAIL, BusinessProfileRegistry::resolveProfile(null, 'General Retail Mart'));
    }

    public function test_store_resolves_default_capabilities_from_profile(): void
    {
        $mobileStore = $this->createTestStore([
            'business_profile' => BusinessProfile::MOBILE_ELECTRONICS,
            'slug'             => 'mobile-cap-test',
        ]);

        $this->assertTrue($mobileStore->hasCapability(Capability::STOREFRONT_GLASS_FINDER));
        $this->assertTrue($mobileStore->hasCapability(Capability::SERVICE_REPAIR_JOBS));
        $this->assertTrue($mobileStore->hasCapability(Capability::INVENTORY_SERIAL_TRACKING));
        $this->assertTrue($mobileStore->hasCapability(Capability::COMMERCE_WHOLESALE));

        $retailStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'slug'             => 'retail-cap-test',
        ]);

        $this->assertFalse($retailStore->hasCapability(Capability::STOREFRONT_GLASS_FINDER));
        $this->assertFalse($retailStore->hasCapability(Capability::SERVICE_REPAIR_JOBS));
        $this->assertTrue($retailStore->hasCapability(Capability::COMMERCE_WHOLESALE));
        $this->assertTrue($retailStore->hasCapability(Capability::CATALOG_BARCODE_PRINTING));
    }

    public function test_pos_only_mode_disables_storefront_capabilities_by_default(): void
    {
        $posOnlyStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'operation_mode'   => BusinessProfile::MODE_POS_ONLY,
            'slug'             => 'pos-only-test',
        ]);

        $this->assertTrue($posOnlyStore->isPosOnly());
        $this->assertFalse($posOnlyStore->isOmnichannel());
        $this->assertFalse($posOnlyStore->hasCapability(Capability::STOREFRONT_ECOMMERCE));
        $this->assertFalse($posOnlyStore->hasCapability(Capability::STOREFRONT_ONLINE_ORDERING));
    }

    public function test_custom_capabilities_override_takes_precedence(): void
    {
        $customStore = $this->createTestStore([
            'business_profile'      => BusinessProfile::GENERAL_RETAIL,
            'capabilities_override' => [
                Capability::SERVICE_REPAIR_JOBS => true,
                Capability::COMMERCE_WHOLESALE  => false,
            ],
            'slug' => 'custom-override-test',
        ]);

        $this->assertTrue($customStore->hasCapability(Capability::SERVICE_REPAIR_JOBS));
        $this->assertFalse($customStore->hasCapability(Capability::COMMERCE_WHOLESALE));
    }

    public function test_disabled_capability_route_aborts_with_403(): void
    {
        // Store with General Retail profile does not have service.repair_jobs capability
        $retailStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'slug'             => 'retail-no-repairs',
        ]);

        $manager = User::factory()->create();
        $retailStore->users()->attach($manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)
            ->get('/store/' . $retailStore->slug . '/admin/repairs');

        $response->assertStatus(403);
    }

    public function test_disabled_capability_json_request_returns_403_json(): void
    {
        $retailStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'slug'             => 'retail-no-glass',
        ]);

        $manager = User::factory()->create();
        $retailStore->users()->attach($manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)
            ->getJson('/store/' . $retailStore->slug . '/admin/glass-finder');

        $response->assertStatus(403);
        $response->assertJson([
            'message'    => 'This feature is not enabled for your store profile.',
            'capability' => Capability::STOREFRONT_GLASS_FINDER,
        ]);
    }

    public function test_enabled_capability_route_allows_access(): void
    {
        $mobileStore = $this->createTestStore([
            'business_profile' => BusinessProfile::MOBILE_ELECTRONICS,
            'slug'             => 'mobile-with-repairs',
        ]);

        $manager = User::factory()->create();
        $mobileStore->users()->attach($manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)
            ->get('/store/' . $mobileStore->slug . '/admin/repairs');

        $response->assertStatus(200);
    }

    public function test_public_glass_finder_blocked_when_store_lacks_capability(): void
    {
        $retailStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'slug'             => 'retail-public-no-glass',
        ]);

        $response = $this->get('/glass-finder?store_slug=' . $retailStore->slug);
        $response->assertStatus(403);
    }

    public function test_admin_sidebar_respects_store_capabilities(): void
    {
        $mobileStore = $this->createTestStore([
            'business_profile' => BusinessProfile::MOBILE_ELECTRONICS,
            'slug'             => 'mobile-sidebar-test',
        ]);

        $manager = User::factory()->create();
        $mobileStore->users()->attach($manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($manager)
            ->get('/store/' . $mobileStore->slug . '/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('store.admin.repairs.index', ['store_slug' => $mobileStore->slug]));
        $response->assertSee(route('store.admin.glass-finder.index', ['store_slug' => $mobileStore->slug]));

        // Retail store
        $retailStore = $this->createTestStore([
            'business_profile' => BusinessProfile::GENERAL_RETAIL,
            'slug'             => 'retail-sidebar-test',
        ]);
        $retailStore->users()->attach($manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $responseRetail = $this->actingAs($manager)
            ->get('/store/' . $retailStore->slug . '/admin/dashboard');

        $responseRetail->assertStatus(200);
        $responseRetail->assertDontSee(route('store.admin.repairs.index', ['store_slug' => $retailStore->slug]));
        $responseRetail->assertDontSee(route('store.admin.glass-finder.index', ['store_slug' => $retailStore->slug]));
    }
}
