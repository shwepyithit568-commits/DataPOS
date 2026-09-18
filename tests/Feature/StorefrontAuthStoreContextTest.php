<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Signing up / signing in on a storefront must stay in THAT store.
 *
 * `/login` and `/register` carry no store in their path, so they resolved the
 * store from the request — and the storefront header linked to them bare, with
 * no `store_slug` anywhere and no hidden field on the form. On a multi-store
 * install every shopper who signed up at store B's storefront was enrolled in
 * the primary store (A) and then landed on A's storefront.
 */
class StorefrontAuthStoreContextTest extends TestCase
{
    use RefreshDatabase;

    private Store $primary;

    private Store $shopB;

    protected function setUp(): void
    {
        parent::setUp();

        // The primary store is what the fallback used to pick — the bug only
        // shows up when it is a DIFFERENT store than the one being browsed.
        $this->primary = Store::create([
            'name' => 'Primary Shop',
            'slug' => 'primary-shop',
            'is_active' => true,
            'is_primary' => true,
        ]);

        $this->shopB = Store::create([
            'name' => 'Second Shop',
            'slug' => 'second-shop',
            'is_active' => true,
            'is_primary' => false,
        ]);
    }

    private function registerPayload(string $phone, ?string $slug): array
    {
        return array_filter([
            'name' => 'Web Shopper',
            'phone' => $phone,
            'password' => 'Customer@2026',
            'password_confirmation' => 'Customer@2026',
            'store_slug' => $slug,
        ], fn ($v) => $v !== null);
    }

    public function test_registering_at_a_storefront_enrolls_the_customer_in_that_store(): void
    {
        $response = $this->post('/register', $this->registerPayload('09970000111', 'second-shop'));

        $user = User::where('phone', '09970000111')->sole();

        $this->assertSame(['second-shop'], $user->stores()->pluck('slug')->all());
        $this->assertSame(
            'retail_customer',
            DB::table('store_user')->where('user_id', $user->id)->where('store_id', $this->shopB->id)->value('role')
        );

        // …and the shopper lands back on the storefront they registered at.
        $response->assertRedirect('/store/second-shop');
    }

    public function test_registering_without_a_store_still_works_and_uses_the_primary_store(): void
    {
        $this->post('/register', $this->registerPayload('09970000222', null));

        $user = User::where('phone', '09970000222')->sole();

        $this->assertSame(['primary-shop'], $user->stores()->pluck('slug')->all());
    }

    public function test_the_storefront_sign_in_links_carry_the_store(): void
    {
        $response = $this->get('/store/second-shop');

        $response->assertOk();
        $response->assertSee('store_slug=second-shop', false);
        $response->assertSee(route('login', ['store_slug' => 'second-shop']), false);
        $response->assertSee(route('register', ['store_slug' => 'second-shop']), false);
    }

    public function test_the_sign_in_form_carries_the_store_into_the_post(): void
    {
        $response = $this->get('/register?store_slug=second-shop');

        $response->assertOk()
            ->assertSee('name="store_slug"', false)
            ->assertSee('value="second-shop"', false);
    }

    public function test_logging_in_at_a_storefront_lands_on_that_store(): void
    {
        $shopper = User::create([
            'name' => 'Two Store Shopper',
            'phone' => '09970000333',
            'password' => bcrypt('Customer@2026'),
            'role' => 'customer',
        ]);
        $shopper->stores()->attach($this->primary->id, ['role' => 'retail_customer', 'status' => 'active']);
        $shopper->stores()->attach($this->shopB->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->post('/login', [
            'phone' => '09970000333',
            'password' => 'Customer@2026',
            'store_slug' => 'second-shop',
        ])->assertRedirect('/store/second-shop');
    }

    /**
     * A store sent back by a stale form must not hijack the sign-in: the
     * shopper only ever lands on a store they actually belong to.
     */
    public function test_a_store_the_shopper_does_not_belong_to_falls_back_to_their_own(): void
    {
        $elsewhere = Store::create([
            'name' => 'Third Shop',
            'slug' => 'third-shop',
            'is_active' => true,
        ]);

        $shopper = User::create([
            'name' => 'Single Store Shopper',
            'phone' => '09970000444',
            'password' => bcrypt('Customer@2026'),
            'role' => 'customer',
        ]);
        $shopper->stores()->attach($this->shopB->id, ['role' => 'retail_customer', 'status' => 'active']);

        $this->post('/login', [
            'phone' => '09970000444',
            'password' => 'Customer@2026',
            'store_slug' => $elsewhere->slug,
        ])->assertRedirect('/store/second-shop');
    }
}
