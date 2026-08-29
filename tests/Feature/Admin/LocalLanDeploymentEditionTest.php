<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Services\LanNetworkService;
use App\Services\LocalBackupPackageService;
use App\Services\OfflineLicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LocalLanDeploymentEditionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected OfflineLicenseService $licenseService;
    protected LanNetworkService $lanService;
    protected LocalBackupPackageService $backupPackageService;
    protected string $secretKey = 'local-offline-secret-key-12345';

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name'      => 'Standalone LAN Store',
            'slug'      => 'standalone-lan-store',
            'is_active' => true,
        ]);

        $this->licenseService = app(OfflineLicenseService::class);
        $this->lanService = app(LanNetworkService::class);
        $this->backupPackageService = app(LocalBackupPackageService::class);
    }

    public function test_offline_license_service_generates_and_verifies_valid_signature(): void
    {
        $payload = [
            'store_name'          => 'Standalone LAN Store',
            'store_slug'          => 'standalone-lan-store',
            'edition'             => 'mobile_electronics',
            'tier'                => 'enterprise',
            'max_branches'        => 5,
            'expires_at'          => now()->addYear()->toDateString(),
            'machine_fingerprint' => $this->licenseService->getMachineFingerprint(),
        ];

        $key = $this->licenseService->generateSignedLicense($payload, $this->secretKey);
        $this->assertNotEmpty($key);

        $result = $this->licenseService->verifyLicense($key, $this->secretKey, 'standalone-lan-store');

        $this->assertTrue($result['is_valid']);
        $this->assertSame('active', $result['status']);
        $this->assertSame('enterprise', $result['payload']['tier']);
    }

    public function test_offline_license_rejects_expired_and_store_mismatch_and_tampered_signature(): void
    {
        // 1. Expired license
        $expiredPayload = [
            'store_name' => 'Standalone LAN Store',
            'store_slug' => 'standalone-lan-store',
            'expires_at' => now()->subDay()->toDateString(),
        ];
        $expiredKey = $this->licenseService->generateSignedLicense($expiredPayload, $this->secretKey);
        $expiredResult = $this->licenseService->verifyLicense($expiredKey, $this->secretKey, 'standalone-lan-store');

        $this->assertFalse($expiredResult['is_valid']);
        $this->assertSame('expired', $expiredResult['status']);

        // 2. Store slug mismatch
        $validPayload = [
            'store_name' => 'Standalone LAN Store',
            'store_slug' => 'standalone-lan-store',
            'expires_at' => now()->addMonths(6)->toDateString(),
        ];
        $validKey = $this->licenseService->generateSignedLicense($validPayload, $this->secretKey);
        $mismatchResult = $this->licenseService->verifyLicense($validKey, $this->secretKey, 'another-hacked-store');

        $this->assertFalse($mismatchResult['is_valid']);
        $this->assertSame('store_mismatch', $mismatchResult['status']);

        // 3. Signature mismatch (wrong secret key)
        $tamperedResult = $this->licenseService->verifyLicense($validKey, 'wrong-secret-tampered-attempt', 'standalone-lan-store');
        $this->assertFalse($tamperedResult['is_valid']);
        $this->assertSame('signature_mismatch', $tamperedResult['status']);
    }

    public function test_lan_network_service_provides_lan_ip_and_pos_url(): void
    {
        $ip = $this->lanService->getServerLanIp();
        $this->assertNotEmpty($ip);

        $posUrl = $this->lanService->getLanPosUrl($this->store, 8501);
        $this->assertStringContainsString('/store/standalone-lan-store/pos', $posUrl);

        $info = $this->lanService->getLanConnectionInfo($this->store, 8501);
        $this->assertArrayHasKey('lan_ip', $info);
        $this->assertArrayHasKey('pos_access_url', $info);
        $this->assertArrayHasKey('instructions', $info);
    }

    public function test_local_backup_package_generates_zip_and_preflight_verifies_checksum(): void
    {
        // Add sample data
        $cat = Category::create([
            'store_id' => $this->store->id,
            'name'     => 'Local Cat',
            'slug'     => 'local-cat',
        ]);
        Product::create([
            'store_id'        => $this->store->id,
            'category_id'     => $cat->id,
            'name'            => 'LAN Item',
            'slug'            => 'lan-item',
            'sku'             => 'LAN-001',
            'retail_price'    => 1000,
            'wholesale_price' => 900,
            'buy_price'       => 700,
        ]);

        $package = $this->backupPackageService->createBackupPackage($this->store);

        $this->assertFileExists($package['filepath']);
        $this->assertNotEmpty($package['sha256']);
        $this->assertGreaterThan(0, $package['size_bytes']);

        // Verify preflight
        $preflight = $this->backupPackageService->verifyBackupPreflight($package['filepath']);

        $this->assertTrue($preflight['is_valid']);
        $this->assertSame('ready_for_restore', $preflight['status']);
        $this->assertSame('DataPOS', $preflight['manifest']['app_name']);

        // Clean up
        if (File::exists($package['filepath'])) {
            File::delete($package['filepath']);
        }
    }
}
