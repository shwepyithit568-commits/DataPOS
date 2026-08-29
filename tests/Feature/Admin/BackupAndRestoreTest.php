<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BackupAndRestoreTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->store = Store::create(['name' => 'Backup Test Store', 'slug' => 'backup-test-store']);
        $this->store->setting()->create(['store_name' => 'Backup Test Store', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['name' => 'Manager Daw Phyu', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Lay', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_access_backups_dashboard(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/backups");

        $response->assertOk();
        $response->assertSee('Backup', false);
    }

    public function test_manager_can_create_backup(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/backups", [
                'format' => 'sql',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_create_full_zip_backup(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/backups", [
                'format' => 'zip',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_download_and_delete_backup(): void
    {
        /** @var DatabaseBackupService $service */
        $service = app(DatabaseBackupService::class);
        $result = $service->create('test');

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/backups/{$result['filename']}/download");

        $response->assertOk();

        $delResponse = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/backups/{$result['filename']}");

        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');
    }

    public function test_manager_can_restore_from_file(): void
    {
        /** @var DatabaseBackupService $service */
        $service = app(DatabaseBackupService::class);
        $result = $service->create('restore_test');

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/backups/restore", [
                'filename' => $result['filename'],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_manager_can_restore_from_upload(): void
    {
        $fakeSql = "-- Sample SQL Dump\nCREATE TABLE IF NOT EXISTS test_backup_tbl (id INT);\n";
        $uploaded = UploadedFile::fake()->createWithContent('backup.sql', $fakeSql);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/backups/upload-restore", [
                'backup_file' => $uploaded,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_staff_cannot_access_backups(): void
    {
        $response = $this->actingAs($this->staff)
            ->get("/store/{$this->store->slug}/admin/backups");

        $response->assertForbidden();
    }
}
