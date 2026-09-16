<?php

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: the offline-sync API used to be completely unauthenticated.
 *
 * Anyone who knew a store slug could push sales (including zero-priced ones
 * that still deducted stock), collect customer debt, backdate records and pull
 * customer PII. Every route now requires the store's sync API key.
 */
class SyncApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private string $key;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'sync-store',
            'name' => 'Sync Store',
            'is_active' => true,
        ]);

        $this->key = $this->store->generateSyncApiKey();
    }

    private function url(string $path): string
    {
        return "/api/v1/store/{$this->store->slug}/sync/{$path}";
    }

    public function test_status_requires_a_key(): void
    {
        $this->getJson($this->url('status'))->assertStatus(401);
    }

    public function test_pull_requires_a_key(): void
    {
        // This endpoint returns customer names, phones and emails.
        $this->getJson($this->url('pull'))->assertStatus(401);
    }

    public function test_push_requires_a_key(): void
    {
        $payload = ['records' => [[
            'client_transaction_id' => 'off-1',
            'record_type'           => 'pos_sale',
            'payload'               => ['lines' => []],
        ]]];

        $this->postJson($this->url('push'), $payload)->assertStatus(401);
        $this->assertDatabaseCount('sync_outbox_records', 0);
    }

    public function test_trigger_requires_a_key(): void
    {
        $this->postJson($this->url('trigger'))->assertStatus(401);
    }

    public function test_a_wrong_key_is_rejected(): void
    {
        $this->getJson($this->url('status'), ['Authorization' => 'Bearer dps_wrong'])
            ->assertStatus(401);
    }

    public function test_an_unknown_store_slug_does_not_leak_existence(): void
    {
        // Must be 401, not 404 — otherwise the endpoint enumerates slugs.
        $this->getJson('/api/v1/store/no-such-store/sync/status', ['Authorization' => "Bearer {$this->key}"])
            ->assertStatus(401);
    }

    public function test_a_valid_key_via_bearer_is_accepted(): void
    {
        $this->getJson($this->url('status'), ['Authorization' => "Bearer {$this->key}"])
            ->assertStatus(200)
            ->assertJsonPath('store', 'sync-store');
    }

    public function test_a_valid_key_via_x_sync_key_header_is_accepted(): void
    {
        $this->getJson($this->url('status'), ['X-Sync-Key' => $this->key])
            ->assertStatus(200);
    }

    public function test_a_revoked_key_is_rejected(): void
    {
        $this->store->revokeSyncApiKey();

        $this->getJson($this->url('status'), ['Authorization' => "Bearer {$this->key}"])
            ->assertStatus(401);
    }

    public function test_rotating_the_key_invalidates_the_previous_one(): void
    {
        $newKey = $this->store->generateSyncApiKey();

        $this->getJson($this->url('status'), ['Authorization' => "Bearer {$this->key}"])
            ->assertStatus(401);
        $this->getJson($this->url('status'), ['Authorization' => "Bearer {$newKey}"])
            ->assertStatus(200);
    }

    public function test_the_key_hash_is_never_serialized(): void
    {
        $this->assertArrayNotHasKey('sync_api_key_hash', $this->store->fresh()->toArray());
        $this->assertNotNull($this->store->fresh()->sync_api_key_hash);
    }

    public function test_push_succeeds_with_a_valid_key(): void
    {
        $payload = ['records' => [[
            'client_transaction_id' => 'off-2',
            'record_type'           => 'expense',
            'payload'               => ['amount' => '5000.00', 'reason' => 'Test'],
        ]]];

        $this->postJson($this->url('push'), $payload, ['X-Sync-Key' => $this->key])
            ->assertStatus(200)
            ->assertJsonPath('store', 'sync-store');
    }

    public function test_the_admin_status_endpoint_still_requires_a_session(): void
    {
        $this->getJson(route('store.admin.sync.status', ['store_slug' => $this->store->slug]))
            ->assertStatus(401);
    }

    public function test_the_admin_status_endpoint_works_for_store_staff(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $staff->stores()->attach($this->store->id, [
            'role'               => 'staff',
            'custom_permissions' => json_encode(['grants' => ['settings.view']]),
        ]);

        $this->actingAs($staff)
            ->getJson(route('store.admin.sync.status', ['store_slug' => $this->store->slug]))
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
