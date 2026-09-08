<?php

namespace Tests\Feature\POS;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ZeroInternetOfflinePilotTest extends TestCase
{
    use RefreshDatabase;

    private CashierShiftService $shifts;
    private InventoryService $inventory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shifts = app(CashierShiftService::class);
        $this->inventory = app(InventoryService::class);
    }

    private function makeStore(string $slug = 'pilot-offline-store'): Store
    {
        return Store::create([
            'name' => 'Offline Pilot Store',
            'slug' => $slug,
            'is_active' => true,
            'currency' => 'MMK',
        ]);
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name' => 'Offline Pilot Staff',
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    /**
     * §14.1 Zero-Internet Dependency:
     * Core POS, closing, and reporting routes must render HTTP 200 with zero external CDN scripts or remote links.
     */
    public function test_core_pos_routes_render_cleanly_with_zero_external_network_dependencies(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $this->shifts->openShift($store, ['register_name' => 'REG-PILOT', 'opening_cash' => 50000], $staff);

        $this->actingAs($staff);

        $routesToVerify = [
            "/store/{$store->slug}/pos",
            "/store/{$store->slug}/pos/closing",
            "/store/{$store->slug}/pos/reports/sales",
            "/store/{$store->slug}/pos/reports/payments",
            "/store/{$store->slug}/pos/reports/cash",
            "/store/{$store->slug}/pos/reports/stock",
            "/store/{$store->slug}/pos/returns",
        ];

        foreach ($routesToVerify as $uri) {
            $response = $this->get($uri);
            $response->assertOk();

            $html = $response->getContent();

            // §14.2 Local Assets: Assert ZERO external CDN script tags
            $this->assertDoesNotMatchRegularExpression(
                '/<script[^>]+src=["\']https?:\/\/(?!localhost|127\.0\.0\.1)[^"\']+["\']/i',
                $html,
                "Route {$uri} contains an external script dependency breaking zero-internet offline operation."
            );

            // Assert ZERO external CDN stylesheets
            $this->assertDoesNotMatchRegularExpression(
                '/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']https?:\/\/(?!localhost|127\.0\.0\.1)[^"\']+["\']/i',
                $html,
                "Route {$uri} contains an external stylesheet dependency breaking zero-internet offline operation."
            );
        }
    }

    /**
     * §14.2 Local Assets:
     * Verify that bundled local fonts and assets exist on disk in public/build.
     */
    public function test_local_fonts_and_vite_manifest_exist_locally(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath, 'Vite production manifest must be compiled locally.');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertIsArray($manifest);

        // Verify CSS and JS assets are mapped
        $this->assertArrayHasKey('resources/css/admin.css', $manifest);
        $this->assertArrayHasKey('resources/js/app-admin.js', $manifest);

        // Verify local font assets exist
        $assetsDir = public_path('build/assets');
        $this->assertDirectoryExists($assetsDir);

        $files = scandir($assetsDir);
        $fontFound = false;
        foreach ($files as $file) {
            if (str_ends_with($file, '.woff2') || str_ends_with($file, '.ttf')) {
                $fontFound = true;
                break;
            }
        }

        $this->assertTrue($fontFound, 'Local offline font bundles (.woff2 / .ttf) must exist locally.');
    }

    /**
     * §14.1 Offline Sync Status API:
     * Status endpoint must return healthy JSON payload without blocking or making remote requests.
     */
    public function test_offline_sync_status_endpoints_respond_cleanly(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);
        $this->actingAs($staff);

        $statusResponse = $this->getJson("/api/v1/store/{$store->slug}/sync/status");
        $statusResponse->assertOk();
        $statusResponse->assertJsonStructure([
            'health' => [
                'pending_count',
                'failed_count',
            ],
        ]);

        $triggerResponse = $this->postJson("/api/v1/store/{$store->slug}/sync/trigger");
        $triggerResponse->assertOk();
    }
}
