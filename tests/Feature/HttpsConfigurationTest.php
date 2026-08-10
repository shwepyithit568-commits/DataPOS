<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HttpsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('config:clear');
        URL::forceScheme(null);
    }

    protected function tearDown(): void
    {
        URL::forceScheme(null);
        Artisan::call('config:clear');

        parent::tearDown();
    }

    public function test_force_https_true_generates_https_urls_in_production(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'https://example.com',
            'app.force_https' => true,
        ]);

        (new AppServiceProvider($this->app))->boot();

        $response = $this->get('http://example.com/login');

        $response->assertOk();
        $response->assertSee('action="https://example.com/login"', false);
    }

    public function test_force_https_false_preserves_http_urls_in_production_like_uat(): void
    {
        URL::forceScheme(null);

        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8500',
            'app.force_https' => false,
        ]);

        (new AppServiceProvider($this->app))->boot();

        $response = $this->get('http://localhost:8500/login');

        $response->assertOk();
        $response->assertSee('action="http://localhost:8500/login"', false);
    }

    public function test_config_cache_preserves_force_https_configuration(): void
    {
        $cachePath = base_path('bootstrap/cache/config.php');

        if (File::exists($cachePath)) {
            File::delete($cachePath);
        }

        $expectedForceHttps = config('app.force_https');

        Artisan::call('config:cache');

        $cachedConfig = require $cachePath;
        $this->assertSame($expectedForceHttps, $cachedConfig['app']['force_https']);
        $this->assertIsBool($cachedConfig['app']['force_https']);

        Artisan::call('config:clear');
    }

    public function test_login_form_does_not_force_https_in_local_uat_mode(): void
    {
        URL::forceScheme(null);
        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8500',
            'app.force_https' => false,
        ]);

        $response = $this->get('http://localhost:8500/login');

        $response->assertOk();
        $response->assertSee('action="http://localhost:8500/login"', false);
        $response->assertDontSee('action="https://localhost:8500/login"', false);
    }

    public function test_order_form_does_not_force_https_in_local_uat_mode(): void
    {
        URL::forceScheme(null);
        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8500',
            'app.force_https' => false,
        ]);

        Store::create(['name' => 'DataPOS', 'slug' => 'datapos-mobile']);

        $response = $this->get('http://localhost:8500/order-builder?store_slug=datapos-mobile');

        $response->assertOk();
        $response->assertSee('action="http://localhost:8500/store/datapos-mobile/orders"', false);
        $response->assertDontSee('action="https://localhost:8500/store/datapos-mobile/orders"', false);
    }

    public function test_import_confirmation_forms_do_not_force_https_in_local_uat_mode(): void
    {
        URL::forceScheme(null);
        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8500',
            'app.force_https' => false,
        ]);

        $store = Store::create(['name' => 'DataPOS', 'slug' => 'datapos-mobile']);
        $manager = User::factory()->create(['phone' => '09123456789']);
        $manager->stores()->attach($store->id, ['role' => 'store_manager', 'status' => 'active']);

        $productPreview = [
            'filename' => 'products.csv',
            'total' => 1,
            'creatable' => 1,
            'updatable' => 0,
            'skipped_duplicate' => 0,
            'failed' => 0,
            'preview_rows' => [],
            'failed_rows' => [],
            'token' => 'product-preview-token',
            'duplicate_strategy' => 'skip',
        ];

        $productResponse = $this->actingAs($manager)
            ->withSession(['import_preview' => $productPreview])
            ->get('http://localhost:8500/store/datapos-mobile/admin/products/import');

        $productResponse->assertOk();
        $productResponse->assertSee('action="http://localhost:8500/store/datapos-mobile/admin/products/import/confirm"', false);
        $productResponse->assertDontSee('action="https://localhost:8500/store/datapos-mobile/admin/products/import/confirm"', false);

        $glassPreview = [
            'filename' => 'glass.csv',
            'total' => 1,
            'valid_rows' => 1,
            'duplicate_rows' => 0,
            'failed' => 0,
            'preview_rows' => [],
            'failed_rows' => [],
            'token' => 'glass-preview-token',
        ];

        $glassResponse = $this->actingAs($manager)
            ->withSession(['import_preview' => $glassPreview])
            ->get('http://localhost:8500/store/datapos-mobile/admin/glass-finder');

        $glassResponse->assertOk();
        $glassResponse->assertSee('action="http://localhost:8500/store/datapos-mobile/admin/glass-finder/import/confirm"', false);
        $glassResponse->assertDontSee('action="https://localhost:8500/store/datapos-mobile/admin/glass-finder/import/confirm"', false);
    }

    public function test_existing_https_behavior_remains_when_enabled(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8500',
            'app.force_https' => true,
        ]);

        (new AppServiceProvider($this->app))->boot();

        $response = $this->get('http://localhost:8500/login');

        $response->assertOk();
        $response->assertSee('action="https://localhost:8500/login"', false);
    }
}
