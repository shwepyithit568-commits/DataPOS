<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: whole-database backup/restore and the database tools act on the
 * shared database (every tenant's rows plus all media), but were gated only by
 * per-store permissions. A manager of ONE store could download and destroy
 * every other store's data.
 */
class PlatformOnlyDatabaseAccessTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $platformOwner;
    private User $storeManager;
    private User $storeOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'guarded-store',
            'name' => 'Guarded Store',
            'is_active' => true,
        ]);

        $this->platformOwner = User::factory()->create(['role' => 'platform_owner']);

        $this->storeManager = User::factory()->create(['role' => 'store_manager']);
        $this->storeManager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->storeOwner = User::factory()->create(['role' => 'store_owner']);
        $this->storeOwner->stores()->attach($this->store->id, ['role' => 'store_owner']);
    }

    private function route(string $name): string
    {
        return route($name, ['store_slug' => $this->store->slug]);
    }

    public function test_backup_index_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->get($this->route('store.admin.backups.index'))
            ->assertStatus(403);
    }

    public function test_backup_index_is_forbidden_for_a_store_owner(): void
    {
        $this->actingAs($this->storeOwner)
            ->get($this->route('store.admin.backups.index'))
            ->assertStatus(403);
    }

    public function test_backup_creation_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->post($this->route('store.admin.backups.store'))
            ->assertStatus(403);
    }

    public function test_backup_download_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->get(route('store.admin.backups.download', [
                'store_slug' => $this->store->slug,
                'file'       => 'whatever.sql',
            ]))
            ->assertStatus(403);
    }

    public function test_backup_restore_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->post($this->route('store.admin.backups.restore'), ['file' => 'whatever.sql'])
            ->assertStatus(403);
    }

    public function test_upload_restore_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->post($this->route('store.admin.backups.upload_restore'))
            ->assertStatus(403);
    }

    public function test_backup_deletion_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->delete(route('store.admin.backups.destroy', [
                'store_slug' => $this->store->slug,
                'file'       => 'whatever.sql',
            ]))
            ->assertStatus(403);
    }

    public function test_database_tools_are_forbidden_for_a_store_owner(): void
    {
        $this->actingAs($this->storeOwner)
            ->get($this->route('store.admin.database.index'))
            ->assertStatus(403);
    }

    public function test_database_vacuum_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->post($this->route('store.admin.database.vacuum'))
            ->assertStatus(403);
    }

    public function test_database_optimize_is_forbidden_for_a_store_manager(): void
    {
        $this->actingAs($this->storeManager)
            ->post($this->route('store.admin.database.optimize'))
            ->assertStatus(403);
    }

    public function test_backup_routes_are_still_reachable_for_the_platform_owner(): void
    {
        $this->actingAs($this->platformOwner)
            ->get($this->route('store.admin.backups.index'))
            ->assertStatus(200);
    }

    /**
     * Proves the 403s above come from the platform-owner gate and not from a
     * missing store permission: the same store owner can reach other store
     * admin pages freely (Store Owner bypasses permission checks entirely).
     */
    public function test_the_same_store_owner_can_reach_other_store_admin_pages(): void
    {
        $this->actingAs($this->storeOwner)
            ->get($this->route('store.admin.branches.index'))
            ->assertStatus(200);
    }

    public function test_an_anonymous_visitor_is_redirected_to_login(): void
    {
        $this->get($this->route('store.admin.backups.index'))
            ->assertRedirect(route('login'));
    }
}
