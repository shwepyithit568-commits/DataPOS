<?php

namespace Tests\Feature\POS;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\DocumentSequence;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\Services\DatabaseBackupService;
use App\Services\LocalBackupPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use ZipArchive;

/**
 * Phase E — Offline & Hardware UAT: Fresh-PC Restore and Backup Integrity Test Suite
 *
 * Covers requirements from docs/myanmar_business_commercial_readiness_plan_v1.md (§13, §14, §19, §20):
 * - §13.1 Full-system backup package export (ZIP containing database SQL + media + manifest).
 * - §13.2 Preflight validation against file corruption, missing manifest, and tampered SHA-256.
 * - §14.3 Disaster Recovery & Fresh-PC Restore: Verifies complete business integrity across restore.
 * - §19/§20 Admin backup management endpoints and AuditLog verification.
 */
class FreshPcRestoreAndBackupIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $name = 'Fresh Restore Tech Store'): Store
    {
        $slug = Str::slug($name) . '-' . Str::random(4);
        $store = Store::create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => true,
            'currency' => 'MMK',
        ]);

        $store->setting()->create([
            'store_name' => $name,
            'receipt_header' => 'Fresh Restore Hub',
            'receipt_footer' => 'Thank you',
        ]);

        return $store;
    }

    private function storeOwner(Store $store): User
    {
        $user = User::create([
            'name' => 'Owner ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'store_owner', 'status' => 'active']);

        return $user;
    }

    /**
     * §13.1 Full System Backup Export UAT:
     * Generates ZIP archive with database.sql, media files, and manifest.json.
     */
    public function test_full_system_backup_package_generation_contains_sql_media_and_manifest(): void
    {
        Storage::fake('local');
        $store = $this->makeStore('Backup Export Hub');

        // Create initial store data
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Mobile Hardware',
            'slug' => 'mobile-hardware-' . Str::random(4),
        ]);
        Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'CCTV DVR 8-Channel',
            'slug' => 'cctv-dvr-8-channel-' . Str::random(4),
            'sku' => 'DVR-8CH-01',
            'purchase_cost' => 120000,
            'retail_price' => 175000,
            'wholesale_price' => 160000,
        ]);

        // Place a mock media file in storage/app/public
        $publicDir = storage_path('app/public');
        if (! File::isDirectory($publicDir)) {
            File::makeDirectory($publicDir, 0755, true);
        }
        $dummyMedia = storage_path('app/public/sample_logo.png');
        file_put_contents($dummyMedia, 'fake_png_data');

        $backupService = new DatabaseBackupService();
        $result = $backupService->create('test_dr', 'zip');

        $this->assertNotEmpty($result['filename']);
        $this->assertSame('zip', $result['format']);
        $this->assertGreaterThan(0, $result['size']);

        // Inspect zip contents
        $zipFullPath = Storage::disk('local')->path(DatabaseBackupService::DIRECTORY . '/' . $result['filename']);
        $this->assertFileExists($zipFullPath);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open($zipFullPath) === true);

        // 1. Verify manifest.json
        $manifestJson = $zip->getFromName('manifest.json');
        $this->assertNotFalse($manifestJson);
        $manifest = json_decode($manifestJson, true);
        $this->assertSame('DataPOS', $manifest['app']);
        $this->assertSame('full_system_backup', $manifest['type']);

        // 2. Verify database.sql
        $sqlDump = $zip->getFromName('database.sql');
        $this->assertNotFalse($sqlDump);
        $this->assertStringContainsString('DataPOS database backup', $sqlDump);
        $this->assertStringContainsString('CCTV DVR 8-Channel', $sqlDump);
        $this->assertStringContainsString('DVR-8CH-01', $sqlDump);

        // 3. Verify media directory entry
        $this->assertNotFalse($zip->getFromName('media/sample_logo.png'));

        $zip->close();

        // Clean up sample file
        @unlink($dummyMedia);
    }

    /**
     * §13.2 Portable Store Backup & SHA-256 Checksum Verification UAT:
     * Generates standalone encrypted/verified package with store_data.json and manifest.
     */
    public function test_local_backup_package_service_generates_verified_portable_package(): void
    {
        $store = $this->makeStore('Portable Backup Store');
        Product::create([
            'store_id' => $store->id,
            'name' => 'HDMI Cable 5M',
            'slug' => 'hdmi-cable-5m-' . Str::random(4),
            'sku' => 'HDMI-5M-001',
            'purchase_cost' => 5000,
            'retail_price' => 9500,
            'wholesale_price' => 8000,
        ]);

        $packageService = new LocalBackupPackageService();
        $package = $packageService->createBackupPackage($store);

        $this->assertFileExists($package['filepath']);
        $this->assertNotEmpty($package['sha256']);
        $this->assertSame(4, $package['manifest']['schema_version']);
        $this->assertSame('sha256', $package['manifest']['checksum_algorithm']);

        // Run preflight verification
        $preflight = $packageService->verifyBackupPreflight($package['filepath']);
        $this->assertTrue($preflight['is_valid']);
        $this->assertSame('ready_for_restore', $preflight['status']);

        // Clean up
        @unlink($package['filepath']);
    }

    /**
     * §13.2 Preflight Tamper & Corruption Detection UAT:
     * Rejects corrupted files, missing manifest, and altered SHA-256 data.
     */
    public function test_backup_preflight_detects_corrupt_missing_manifest_and_tampered_checksum(): void
    {
        $store = $this->makeStore('Preflight Integrity Hub');
        $packageService = new LocalBackupPackageService();

        // 1. Non-existent file
        $nonExistent = $packageService->verifyBackupPreflight(storage_path('app/backups/non_existent.zip'));
        $this->assertFalse($nonExistent['is_valid']);
        $this->assertSame('file_not_found', $nonExistent['status']);

        // 2. Corrupt archive (non-zip text saved with .zip extension)
        $corruptFile = storage_path('app/backups/corrupt_' . uniqid() . '.zip');
        File::ensureDirectoryExists(dirname($corruptFile));
        file_put_contents($corruptFile, 'INVALID_CORRUPTED_BINARY_DATA_NOT_A_ZIP');
        $corruptResult = $packageService->verifyBackupPreflight($corruptFile);
        $this->assertFalse($corruptResult['is_valid']);
        $this->assertSame('corrupt_archive', $corruptResult['status']);
        @unlink($corruptFile);

        // 3. Missing manifest in ZIP
        $noManifestFile = storage_path('app/backups/no_manifest_' . uniqid() . '.zip');
        $zipNoManifest = new ZipArchive();
        $zipNoManifest->open($noManifestFile, ZipArchive::CREATE);
        $zipNoManifest->addFromString('store_data.json', json_encode(['foo' => 'bar']));
        $zipNoManifest->close();

        $missingManifestResult = $packageService->verifyBackupPreflight($noManifestFile);
        $this->assertFalse($missingManifestResult['is_valid']);
        $this->assertSame('missing_manifest', $missingManifestResult['status']);
        @unlink($noManifestFile);

        // 4. Tampered checksum (modified data file after manifest generation)
        $tamperedFile = storage_path('app/backups/tampered_' . uniqid() . '.zip');
        $originalData = json_encode(['balance' => 50000]);
        $originalHash = hash('sha256', $originalData);

        $manifest = [
            'app_name' => 'DataPOS',
            'version' => '2.0.0',
            'data_file' => 'store_data.json',
            'data_sha256' => $originalHash, // Hash of original data
        ];

        $zipTampered = new ZipArchive();
        $zipTampered->open($tamperedFile, ZipArchive::CREATE);
        $zipTampered->addFromString('manifest.json', json_encode($manifest));
        // Inject tampered data that doesn't match originalHash
        $zipTampered->addFromString('store_data.json', json_encode(['balance' => 999999999]));
        $zipTampered->close();

        $tamperedResult = $packageService->verifyBackupPreflight($tamperedFile);
        $this->assertFalse($tamperedResult['is_valid']);
        $this->assertSame('checksum_mismatch', $tamperedResult['status']);
        @unlink($tamperedFile);
    }

    /**
     * §14.3 Disaster Recovery & Fresh-PC Restore Business Integrity UAT:
     * Restores complete store data from SQL dump and verifies business entities match.
     */
    public function test_end_to_end_fresh_pc_restore_restores_database_and_verifies_business_integrity(): void
    {
        $store = $this->makeStore('Mission Critical Tech Hub');
        $cashier = $this->storeOwner($store);

        // 1. Seed comprehensive business state
        $category = Category::create([
            'store_id' => $store->id,
            'name' => 'Networking',
            'slug' => 'networking-' . Str::random(4),
        ]);
        $product = Product::create([
            'store_id' => $store->id,
            'category_id' => $category->id,
            'name' => 'Gigabit Managed Switch 24-Port',
            'slug' => 'gigabit-managed-switch-24-port-' . Str::random(4),
            'sku' => 'SW-24G-01',
            'purchase_cost' => 250000,
            'retail_price' => 380000,
            'wholesale_price' => 350000,
        ]);

        $customerUser = User::create([
            'name' => 'Ko Aung Myo (Gold Contractor)',
            'phone' => '09777888999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $customerUser->stores()->attach($store->id, ['role' => 'customer', 'status' => 'active']);

        // Record customer receivable in ledger
        CustomerLedgerEntry::create([
            'store_id' => $store->id,
            'customer_id' => $customerUser->id,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => 150000,
            'created_by' => $cashier->id,
            'occurred_at' => now(),
            'notes' => 'Store renovation equipment debt',
        ]);

        $sale = PosSale::create([
            'store_id' => $store->id,
            'cashier_id' => $cashier->id,
            'customer_id' => $customerUser->id,
            'receipt_number' => 'RCP-RESTORE-TEST-001',
            'status' => 'posted',
            'subtotal' => 380000,
            'discount' => 0,
            'tax' => 0,
            'total' => 380000,
            'posted_at' => now(),
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'unit_price' => 380000,
            'quantity' => 1,
            'line_total' => 380000,
        ]);

        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 380000,
            'created_by' => $cashier->id,
        ]);

        DocumentSequence::create([
            'store_id' => $store->id,
            'document_type' => 'pos_sale',
            'period_key' => date('Ymd'),
            'last_number' => 88,
        ]);

        // 2. Generate Full Database SQL Dump via DatabaseBackupService
        $backupService = new DatabaseBackupService();
        $pdo = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();

        $refMethod = new \ReflectionMethod(DatabaseBackupService::class, 'dump');
        $refMethod->setAccessible(true);
        $sqlDump = $refMethod->invoke($backupService, $pdo, $driver);

        $this->assertNotEmpty($sqlDump);
        $this->assertStringContainsString('Gigabit Managed Switch 24-Port', $sqlDump);
        $this->assertStringContainsString('Ko Aung Myo (Gold Contractor)', $sqlDump);
        $this->assertStringContainsString('RCP-RESTORE-TEST-001', $sqlDump);

        // 3. Simulate disaster / database distortion
        $product->update(['retail_price' => 1]);
        CustomerLedgerEntry::where('store_id', $store->id)->delete();
        PosSale::where('id', $sale->id)->delete();

        $this->assertEquals(1, (int) Product::find($product->id)->retail_price);
        $this->assertNull(PosSale::find($sale->id));
        $this->assertEquals(0, CustomerLedgerEntry::where('store_id', $store->id)->count());

        // 4. Perform Fresh Restore from SQL
        if (DB::transactionLevel() > 0) {
            DB::commit();
        }
        $backupService->restoreFromSql($sqlDump);

        // 5. Verify 100% Business Integrity Restored
        $restoredProduct = Product::find($product->id);
        $this->assertNotNull($restoredProduct);
        $this->assertEquals(380000, (int) $restoredProduct->retail_price);

        $restoredDebt = CustomerLedgerEntry::where('store_id', $store->id)->first();
        $this->assertNotNull($restoredDebt);
        $this->assertEquals(150000, (int) $restoredDebt->amount);

        $restoredSale = PosSale::where('receipt_number', 'RCP-RESTORE-TEST-001')->first();
        $this->assertNotNull($restoredSale);
        $this->assertEquals(380000, (int) $restoredSale->total);
        $this->assertSame('posted', $restoredSale->status);

        $restoredSequence = DocumentSequence::where('store_id', $store->id)
            ->where('document_type', 'pos_sale')
            ->first();
        $this->assertNotNull($restoredSequence);
        $this->assertEquals(88, $restoredSequence->last_number);
    }

    /**
     * §19/§20 Admin Backup Management UI & AuditLog UAT:
     * Store Owner can view backup lists, trigger manual backup, and download file.
     */
    public function test_backup_controller_endpoints_for_store_owner(): void
    {
        Storage::fake('local');
        $store = $this->makeStore('Admin Backup Control Hub');
        $owner = $this->storeOwner($store);

        $this->actingAs($owner);

        // 1. View Backups Index
        $indexResponse = $this->get("/store/{$store->slug}/admin/backups");
        $indexResponse->assertOk();
        $indexResponse->assertSee('Admin Backup Control Hub');

        // 2. Trigger Manual Backup Creation
        $createResponse = $this->post("/store/{$store->slug}/admin/backups", [
            'format' => 'sql',
        ]);
        $createResponse->assertRedirect(route('store.admin.backups.index', ['store_slug' => $store->slug]));

        // Verify AuditLog written
        $audit = AuditLog::where('store_id', $store->id)
            ->where('action', 'database_backup_created')
            ->first();
        $this->assertNotNull($audit);
        $this->assertSame('backup', $audit->entity_type);
        $this->assertSame($owner->id, $audit->actor_id);

        // 3. Verify Download Backup File
        $backupService = new DatabaseBackupService();
        $backups = $backupService->list();
        $this->assertNotEmpty($backups);
        $filename = $backups[0]['filename'];

        $downloadResponse = $this->get("/store/{$store->slug}/admin/backups/{$filename}/download");
        $downloadResponse->assertOk();
        $this->assertStringContainsString($filename, $downloadResponse->headers->get('content-disposition'));

        // 4. Delete Backup File
        $deleteResponse = $this->delete("/store/{$store->slug}/admin/backups/{$filename}");
        $deleteResponse->assertRedirect(route('store.admin.backups.index', ['store_slug' => $store->slug]));
        $this->assertFalse($backupService->exists($filename));
    }
}
