<?php

namespace Tests\Feature\Admin;

use App\Models\Promotion;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-promo-store',
            'name' => 'Test Promo Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);
    }

    public function test_promotions_index_renders(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.promotions.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee(__('messages.promotion_title'));
    }

    public function test_create_percent_off_promotion(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.promotions.store', ['store_slug' => $this->store->slug]), [
                'name'             => 'Thingyan Sale 10%',
                'code'             => 'THINGYAN10',
                'type'             => 'percent_off',
                'value'            => 10.0,
                'min_order_amount' => 50000,
                'is_active'        => 1,
                'is_public'        => 0,
            ]);

        $response->assertRedirect(route('store.admin.promotions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'store_id' => $this->store->id,
            'name'     => 'Thingyan Sale 10%',
            'code'     => 'THINGYAN10',
            'type'     => 'percent_off',
            'value'    => 10.0,
        ]);
    }

    public function test_create_flat_off_promotion(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.promotions.store', ['store_slug' => $this->store->slug]), [
                'name'  => 'Weekend Flat Discount',
                'type'  => 'flat_off',
                'value' => 5000.0,
                'is_active' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('promotions', [
            'store_id' => $this->store->id,
            'type'     => 'flat_off',
            'value'    => 5000.0,
        ]);
    }

    public function test_update_promotion(): void
    {
        $promo = Promotion::create([
            'store_id'  => $this->store->id,
            'name'      => 'Old Name',
            'type'      => 'percent_off',
            'value'     => 5.0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.promotions.update', ['store_slug' => $this->store->slug, 'promotion' => $promo->id]), [
                'name'      => 'Updated Sale',
                'type'      => 'percent_off',
                'value'     => 15.0,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.promotions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');
        $this->assertEquals('Updated Sale', $promo->fresh()->name);
        $this->assertEquals(15.0, $promo->fresh()->value);
    }

    public function test_toggle_promotion_active(): void
    {
        $promo = Promotion::create([
            'store_id'  => $this->store->id,
            'name'      => 'Toggle Test',
            'type'      => 'flat_off',
            'value'     => 2000,
            'is_active' => true,
        ]);

        $this->actingAs($this->manager)
            ->post(route('store.admin.promotions.toggle', ['store_slug' => $this->store->slug, 'promotion' => $promo->id]));

        $this->assertFalse($promo->fresh()->is_active);

        $this->actingAs($this->manager)
            ->post(route('store.admin.promotions.toggle', ['store_slug' => $this->store->slug, 'promotion' => $promo->id]));

        $this->assertTrue($promo->fresh()->is_active);
    }

    public function test_delete_promotion(): void
    {
        $promo = Promotion::create([
            'store_id'  => $this->store->id,
            'name'      => 'To Delete',
            'type'      => 'percent_off',
            'value'     => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.promotions.destroy', ['store_slug' => $this->store->slug, 'promotion' => $promo->id]));

        $response->assertRedirect(route('store.admin.promotions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('promotions', ['id' => $promo->id]);
    }

    public function test_validate_coupon_valid(): void
    {
        Promotion::create([
            'store_id'         => $this->store->id,
            'name'             => 'Sale 10%',
            'code'             => 'SALE10',
            'type'             => 'percent_off',
            'value'            => 10.0,
            'min_order_amount' => 10000,
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('store.admin.promotions.validate_coupon', ['store_slug' => $this->store->slug]) . '?code=SALE10&order_total=50000');

        $response->assertStatus(200);
        $response->assertJson(['valid' => true]);
        $this->assertEquals(5000.0, $response->json('discount'));
    }

    public function test_validate_coupon_invalid_minimum_order(): void
    {
        Promotion::create([
            'store_id'         => $this->store->id,
            'name'             => 'Sale 10%',
            'code'             => 'SALEMINTEST',
            'type'             => 'percent_off',
            'value'            => 10.0,
            'min_order_amount' => 100000,
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->getJson(route('store.admin.promotions.validate_coupon', ['store_slug' => $this->store->slug]) . '?code=SALEMINTEST&order_total=50000');

        $response->assertStatus(200);
        $response->assertJson(['valid' => false]);
    }

    public function test_duplicate_coupon_code_rejected(): void
    {
        Promotion::create([
            'store_id'  => $this->store->id,
            'name'      => 'Existing',
            'code'      => 'DUPCODE',
            'type'      => 'flat_off',
            'value'     => 1000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.promotions.store', ['store_slug' => $this->store->slug]), [
                'name'  => 'Another with same code',
                'code'  => 'DUPCODE',
                'type'  => 'flat_off',
                'value' => 2000,
            ]);

        $response->assertSessionHasErrors('code');
    }
}
