<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StorefrontSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront is cached in the browser for anonymous visitors only.
 *
 * `CachePublicPage` opts public storefront GETs into `private, max-age=60` +
 * ETag. That response also carries a CSRF token and — once someone signs in —
 * the shopper's own name and points. A browser copy that outlives a login or a
 * logout then hands the next POST a token the server no longer knows: the
 * measured symptom was a storefront logout answering **419 Page expired**,
 * leaving the visitor still signed in.
 */
class StorefrontCachePolicyTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private User $shopper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Cache Shop',
            'slug' => 'cache-shop',
            'is_active' => true,
        ]);

        StorefrontSetting::create([
            'store_id' => $this->store->id,
            'store_name' => $this->store->name,
        ]);

        $this->shopper = User::create([
            'name' => 'Cache Shopper',
            'phone' => '09970000555',
            'password' => bcrypt('Customer@2026'),
            'role' => 'customer',
        ]);
        $this->shopper->stores()->attach($this->store->id, ['role' => 'retail_customer', 'status' => 'active']);
    }

    public function test_an_anonymous_visitor_still_gets_the_cached_storefront(): void
    {
        $response = $this->get('/store/cache-shop');

        $response->assertOk();
        $this->assertStringContainsString('max-age=60', (string) $response->headers->get('Cache-Control'));
        $this->assertNotNull($response->headers->get('ETag'));
    }

    public function test_a_signed_in_shopper_gets_an_uncached_storefront(): void
    {
        $response = $this->actingAs($this->shopper)->get('/store/cache-shop');

        $response->assertOk();
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $this->assertNull($response->headers->get('ETag'));
        // …and the page still renders the shopper's own session.
        $response->assertSee('Cache Shopper', false);
    }
}
