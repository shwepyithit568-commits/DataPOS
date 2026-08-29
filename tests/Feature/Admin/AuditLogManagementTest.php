<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;
    protected Store $otherStore;
    protected User $otherManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Security Store', 'slug' => 'security-store']);
        $this->store->setting()->create(['store_name' => 'Security Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Owner Ko Zaw', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Win', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);

        $this->otherStore = Store::create(['name' => 'Other Store', 'slug' => 'other-store']);
        $this->otherStore->setting()->create(['store_name' => 'Other Store', 'default_language' => 'en']);

        $this->otherManager = User::factory()->create(['name' => 'Other Manager', 'phone' => '09777888999']);
        $this->otherManager->stores()->attach($this->otherStore->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_manager_can_access_audit_logs_dashboard(): void
    {
        AuditLog::create([
            'store_id' => $this->store->id,
            'user_id'  => $this->manager->id,
            'action'   => 'product_price_changed',
            'details'  => ['product_id' => 1, 'old_price' => 1000, 'new_price' => 1200],
            'ip'       => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/audit-logs");

        $response->assertOk();
        $response->assertSee('product_price_changed');
    }

    public function test_audit_logs_filters_by_category_and_search(): void
    {
        AuditLog::create([
            'store_id' => $this->store->id,
            'user_id'  => $this->manager->id,
            'action'   => 'product_price_changed',
            'details'  => ['name' => 'Special Screen Protector'],
            'ip'       => '127.0.0.1',
        ]);

        AuditLog::create([
            'store_id' => $this->store->id,
            'user_id'  => $this->manager->id,
            'action'   => 'daily_closing_approved',
            'details'  => ['shift_id' => 5],
            'ip'       => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/audit-logs?category=pricing_sales");

        $response->assertOk();
        $response->assertSee('product_price_changed');
        $response->assertDontSee('daily_closing_approved');
    }

    public function test_audit_logs_export_csv(): void
    {
        AuditLog::create([
            'store_id' => $this->store->id,
            'user_id'  => $this->manager->id,
            'action'   => 'bulk_price_updated',
            'details'  => ['count' => 15],
            'ip'       => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/audit-logs/export");

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('bulk_price_updated', $content);
    }

    public function test_audit_logs_store_isolation(): void
    {
        AuditLog::create([
            'store_id' => $this->otherStore->id,
            'user_id'  => $this->otherManager->id,
            'action'   => 'secret_action_other_store',
            'details'  => [],
            'ip'       => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/security/audit-logs");

        $response->assertOk();
        $response->assertDontSee('secret_action_other_store');
    }

    public function test_staff_cannot_access_audit_logs(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/security/audit-logs");

        $response->assertForbidden();
    }
}
