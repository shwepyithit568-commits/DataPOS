<?php

namespace Tests\Feature;

use App\Models\Store;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionSeedingSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_safe_seeder_does_not_create_demo_users(): void
    {
        $this->seed(ProductionSeeder::class);

        foreach ($this->demoPhones() as $phone) {
            $this->assertDatabaseMissing('users', ['phone' => $phone]);
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('stores', 0);
    }

    public function test_production_safe_seeder_is_idempotent(): void
    {
        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('stores', 0);
    }

    public function test_default_password_hints_do_not_appear_in_production_login_html(): void
    {
        config([
            'app.env' => 'production',
            'app.show_quick_login' => false,
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('PWD: password', false);
        $response->assertDontSee('password123', false);
        $response->assertDontSee('12345678', false);
        $response->assertDontSee('admin123', false);
        $response->assertDontSee('test1234', false);

        foreach ($this->demoPhones() as $phone) {
            $response->assertDontSee($phone, false);
        }
    }

    public function test_storefront_does_not_render_test_contact_fallbacks_in_production(): void
    {
        config(['app.env' => 'production']);

        Store::create([
            'name' => 'Production Store',
            'slug' => 'production-store',
            'is_active' => true,
        ]);

        $response = $this->get('/?store_slug=production-store');

        $response->assertOk();
        $response->assertDontSee('09123456789', false);
        $response->assertDontSee('viber://chat?number=09123456789', false);
        $response->assertDontSee('https://t.me/datapos', false);
    }

    /**
     * @return array<int, string>
     */
    private function demoPhones(): array
    {
        return [
            '09100000001',
            '09100000002',
            '09100000003',
            '09100000004',
            '09100000005',
            '09100000006',
            '09100000007',
        ];
    }
}
