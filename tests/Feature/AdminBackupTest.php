<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->store = Store::create(['name' => 'DataPOS Backup', 'slug' => 'datapos-backup']);

        $this->manager = User::factory()->create(['phone' => '09111111001']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['phone' => '09111111002']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_service_creates_sql_dump_with_schema_and_data(): void
    {
        $service = new DatabaseBackupService();
        $result = $service->create('test', 'sql');

        $this->assertStringEndsWith('.sql', $result['filename']);
        $this->assertGreaterThan(0, $result['size']);

        $relative = $service->path($result['filename']);
        $this->assertTrue(Storage::disk('local')->exists($relative));

        $content = Storage::disk('local')->get($relative);
        $this->assertStringContainsString('CREATE TABLE', $content);
        $this->assertStringContainsString('INSERT INTO', $content);
        $this->assertStringContainsString('DataPOS database backup', $content);
    }

    public function test_manager_can_view_backups_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/backups");

        $response->assertOk();
        $response->assertSee(__('messages.backup_title'));
        $response->assertSee(__('messages.backup_empty'));
    }

    public function test_staff_cannot_access_backups_page(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/backups");

        $response->assertForbidden();
    }

    public function test_manager_can_create_backup_from_admin(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/backups");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $files = Storage::disk('local')->files('backups');
        $this->assertCount(1, $files);
        $this->assertTrue(str_ends_with($files[0], '.zip') || str_ends_with($files[0], '.sql'));
    }

    public function test_manager_can_download_backup(): void
    {
        $service = new DatabaseBackupService();
        $result = $service->create('download');

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/backups/{$result['filename']}/download");

        $response->assertOk();
        $response->assertDownload($result['filename']);
    }

    public function test_download_missing_backup_returns_404(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/backups/nope_2020-01-01_000000.sqlite.sql/download");

        $response->assertNotFound();
    }

    public function test_manager_can_delete_backup(): void
    {
        $service = new DatabaseBackupService();
        $result = $service->create('delete-me');

        $response = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/backups/{$result['filename']}");

        $response->assertRedirect();
        $this->assertFalse(Storage::disk('local')->exists($service->path($result['filename'])));
    }

    public function test_service_prunes_old_backups(): void
    {
        $service = new DatabaseBackupService();

        // Create more than KEEP backups (each with a distinct fake mtime).
        for ($i = 0; $i < DatabaseBackupService::KEEP + 3; $i++) {
            $result = $service->create('prune-' . $i);
            // Rewind the file mtime so it is clearly older.
            $full = Storage::disk('local')->path($service->path($result['filename']));
            touch($full, now()->subDays(DatabaseBackupService::KEEP + $i)->timestamp);
        }

        // Trigger pruning via another create.
        $service->create('final');

        $files = Storage::disk('local')->files('backups');
        $this->assertLessThanOrEqual(DatabaseBackupService::KEEP + 1, count($files));
    }
}
