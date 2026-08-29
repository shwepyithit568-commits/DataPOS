<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseToolTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Database Test Store', 'slug' => 'database-test-store']);
        $this->store->setting()->create(['store_name' => 'Database Test Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager U Hla', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Tun', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_access_database_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/database");

        $response->assertOk();
        $response->assertSee('Database Tools', false);
        $response->assertSee('Vacuum', false);
        $response->assertSee('Integrity Health Check', false);
    }

    public function test_manager_can_execute_vacuum(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/database/vacuum");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_execute_optimize(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/database/optimize");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_execute_integrity_check(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/database/integrity-check");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_clear_cache(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/database/clear-cache");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_staff_without_permission_is_forbidden(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/database");

        $response->assertForbidden();
    }
}
