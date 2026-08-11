<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Services\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Multi-store-ready plan §7.1 — resolver fallback behavior on the root
 * storefront home (`/`) when no store_slug / header / query context is given.
 */
class StoreContextResolverTest extends TestCase
{
    use RefreshDatabase;

    private function resolvedStoreId(): ?int
    {
        return app(StoreContext::class)->getStoreId();
    }

    private function createStore(string $name, string $slug, array $extra = []): Store
    {
        return Store::create(array_merge([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'is_primary' => false,
        ], $extra));
    }

    public function test_two_active_stores_with_primary_resolves_primary_on_root(): void
    {
        $primary = $this->createStore('Primary Alpha', 'store-alpha', ['is_primary' => true]);
        $this->createStore('Secondary Beta', 'store-beta');

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertSame($primary->id, $this->resolvedStoreId());
        $response->assertSee('Primary Alpha', false);
        $response->assertDontSee('Secondary Beta');
    }

    public function test_no_primary_single_active_store_legacy_fallback(): void
    {
        $only = $this->createStore('Only Store', 'only-store');

        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertSame($only->id, $this->resolvedStoreId());
        $response->assertSee('Only Store', false);
    }

    public function test_no_primary_two_active_stores_returns_clear_empty_state(): void
    {
        $this->createStore('First Gamma', 'store-gamma');
        $this->createStore('Second Delta', 'store-delta');

        $response = $this->get('/');

        // Ambiguous fallback: resolver stays null; the home page must render a
        // clear empty state instead of crashing or leaking one store's data.
        $response->assertStatus(200);
        $this->assertNull($this->resolvedStoreId());
        $response->assertDontSee('First Gamma');
        $response->assertDontSee('Second Delta');
    }

    public function test_logged_in_user_store_membership_wins_over_primary(): void
    {
        $primary = $this->createStore('Primary Epsilon', 'store-epsilon', ['is_primary' => true]);
        $member = $this->createStore('Member Zeta', 'store-zeta');

        $user = User::create([
            'name' => 'Manager',
            'phone' => '09444444444',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($member->id, ['role' => 'store_manager', 'status' => 'active']);

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
        $this->assertSame($member->id, $this->resolvedStoreId());
        $response->assertSee('Member Zeta', false);
        $response->assertDontSee('Primary Epsilon');
    }

    public function test_platform_owner_without_membership_resolves_primary(): void
    {
        $primary = $this->createStore('Primary Eta', 'store-eta', ['is_primary' => true]);
        $this->createStore('Other Theta', 'store-theta');

        $owner = User::create([
            'name' => 'Platform Owner',
            'phone' => '09555555555',
            'password' => bcrypt('password'),
            'role' => 'platform_owner',
        ]);

        $response = $this->actingAs($owner)->get('/');

        $response->assertStatus(200);
        $this->assertSame($primary->id, $this->resolvedStoreId());
    }

    public function test_two_primary_stores_resolves_deterministically_by_lowest_id(): void
    {
        $first = $this->createStore('Primary Iota', 'store-iota', ['is_primary' => true]);
        $this->createStore('Primary Kappa', 'store-kappa', ['is_primary' => true]);

        $response = $this->get('/');

        // Data drift guard: never return null when primaries exist — pick the
        // lowest id deterministically.
        $response->assertStatus(200);
        $this->assertSame($first->id, $this->resolvedStoreId());
        $response->assertSee('Primary Iota', false);
    }
}
