<?php

namespace Tests\Feature\Admin;

use App\Models\MembershipTier;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\MembershipLoyaltyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipLoyaltyTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-loyalty-store',
            'name' => 'Test Loyalty Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->customer = User::factory()->create(['role' => 'retail_customer']);
        $this->customer->stores()->attach($this->store->id, [
            'role' => 'retail_customer',
            'status' => 'active',
            'loyalty_points' => 150,
            'total_spent' => 250000.0,
        ]);
    }

    public function test_membership_index_renders_with_default_tiers(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('Standard Member');
        $response->assertSee('Silver VIP');
        $response->assertSee('Gold VIP');
        $response->assertSee('Platinum VIP');
        $response->assertSee(__('messages.membership_title'));
    }

    public function test_create_custom_membership_tier(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.membership.tiers.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Diamond VIP',
                'code' => 'DIAMOND',
                'min_spending' => 5000000.0,
                'discount_percent' => 15.0,
                'point_multiplier' => 2.5,
                'badge_color' => 'purple',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('membership_tiers', [
            'store_id' => $this->store->id,
            'name' => 'Diamond VIP',
            'code' => 'DIAMOND',
            'discount_percent' => 15.0,
        ]);
    }

    public function test_update_membership_tier(): void
    {
        $service = app(MembershipLoyaltyService::class);
        $service->ensureDefaultTiers($this->store);

        $silver = MembershipTier::where('store_id', $this->store->id)->where('code', 'SILVER')->first();

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.membership.tiers.update', [
                'store_slug' => $this->store->slug,
                'tier' => $silver->id,
            ]), [
                'name' => 'Silver VIP Super',
                'code' => 'SILVER',
                'min_spending' => 300000.0,
                'discount_percent' => 4.0,
                'point_multiplier' => 1.3,
                'badge_color' => 'blue',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertEquals('Silver VIP Super', $silver->fresh()->name);
        $this->assertEquals(4.0, $silver->fresh()->discount_percent);
    }

    public function test_cannot_delete_default_tier(): void
    {
        $service = app(MembershipLoyaltyService::class);
        $service->ensureDefaultTiers($this->store);

        $default = MembershipTier::where('store_id', $this->store->id)->where('is_default', true)->first();

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.membership.tiers.destroy', [
                'store_slug' => $this->store->slug,
                'tier' => $default->id,
            ]));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('membership_tiers', ['id' => $default->id]);
    }

    public function test_delete_custom_tier(): void
    {
        $service = app(MembershipLoyaltyService::class);
        $service->ensureDefaultTiers($this->store);

        $tier = MembershipTier::create([
            'store_id' => $this->store->id,
            'name' => 'Tier to Delete',
            'code' => 'DEL',
            'min_spending' => 10000,
            'discount_percent' => 1,
            'point_multiplier' => 1,
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.membership.tiers.destroy', [
                'store_slug' => $this->store->slug,
                'tier' => $tier->id,
            ]));

        $response->assertRedirect(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('membership_tiers', ['id' => $tier->id]);
    }

    public function test_manual_loyalty_point_adjustment(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.membership.adjust_points', ['store_slug' => $this->store->slug]), [
                'customer_id' => $this->customer->id,
                'points' => 200,
                'type' => 'bonus',
                'notes' => 'Store anniversary gift',
            ]);

        $response->assertRedirect(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'points' => 200,
            'balance_after' => 350,
            'type' => 'bonus',
        ]);
    }

    public function test_assign_customer_membership_tier(): void
    {
        $service = app(MembershipLoyaltyService::class);
        $service->ensureDefaultTiers($this->store);

        $gold = MembershipTier::where('store_id', $this->store->id)->where('code', 'GOLD')->first();

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.membership.assign_tier', ['store_slug' => $this->store->slug]), [
                'customer_id' => $this->customer->id,
                'tier_id' => $gold->id,
            ]);

        $response->assertRedirect(route('store.admin.membership.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('store_user', [
            'store_id' => $this->store->id,
            'user_id' => $this->customer->id,
            'membership_tier_id' => $gold->id,
        ]);
    }

    public function test_membership_index_renders_in_all_supported_locales_without_key_leaks(): void
    {
        foreach (['en', 'my', 'zh_CN'] as $code) {
            $store = Store::create(['name' => "Store Member {$code}", 'slug' => "store-mem-{$code}"]);
            $store->setting()->create(['store_name' => "Store Member {$code}", 'default_language' => $code]);
            $this->manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

            $response = $this->actingAs($this->manager)
                ->get("/store/{$store->slug}/admin/membership");

            $response->assertStatus(200);
            $response->assertDontSee('messages.', false);
        }
    }
}
