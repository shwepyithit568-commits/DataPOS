<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceSettingTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $manager;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'DataPOS Mobile',
            'slug' => 'datapos-mobile',
            'currency' => 'MMK',
        ]);

        $this->manager = User::factory()->create();
        $this->store->users()->attach($this->manager->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create();
        $this->store->users()->attach($this->staff->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_view_service_settings(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/service-settings");

        $response->assertStatus(200);
        $response->assertSeeText('Apple');
        $response->assertSeeText('Samsung');
    }

    public function test_manager_can_create_service_setting(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/service-settings", [
                'type' => 'brand',
                'name' => 'Nothing Phone',
                'sort_order' => 5,
                'is_active' => '1',
            ]);

        $response->assertRedirect("/store/{$this->store->slug}/admin/service-settings?tab=brand");

        $this->assertDatabaseHas('service_settings', [
            'store_id' => $this->store->id,
            'type' => 'brand',
            'name' => 'Nothing Phone',
        ]);
    }

    public function test_quick_add_endpoint_creates_option(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/service-settings/quick-add", [
                'type' => 'color',
                'name' => 'Titanium Violet',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'item' => [
                'name' => 'Titanium Violet',
                'type' => 'color',
            ],
        ]);

        $this->assertDatabaseHas('service_settings', [
            'store_id' => $this->store->id,
            'type' => 'color',
            'name' => 'Titanium Violet',
        ]);
    }

    public function test_repair_create_saves_extended_fields_and_advance_payment(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/repairs", [
                'contact_name' => 'Ko Aung',
                'contact_phone' => '09123456789',
                'shipping_address' => 'No. 123, Bogyoke St, Yangon',
                'brand' => 'Apple',
                'category' => 'Smartphone',
                'model' => 'iPhone 14 Pro Max',
                'color' => 'Space Black',
                'storage' => '256 GB',
                'imei_serial' => '354892091234567',
                'reported_problem' => 'Screen glass broken and touch delay',
                'accessories' => 'SIM Tray, Phone Case',
                'pattern_lock' => '1-2-3-5-7',
                'device_password' => '998877',
                'advance_payment' => '30000',
                'payment_method' => 'kpay',
                'estimated_charge' => '85000',
            ]);

        /** @var ServiceJob $job */
        $job = ServiceJob::where('store_id', $this->store->id)->latest('id')->first();
        $this->assertNotNull($job);

        $response->assertRedirect("/store/{$this->store->slug}/admin/repairs/{$job->id}");

        $this->assertEquals('Apple', $job->brand);
        $this->assertEquals('iPhone 14 Pro Max', $job->model);
        $this->assertEquals('Space Black', $job->color);
        $this->assertEquals('256 GB', $job->storage);
        $this->assertEquals('1-2-3-5-7', $job->pattern_lock);
        $this->assertEquals('998877', $job->device_password);
        $this->assertEquals('No. 123, Bogyoke St, Yangon', $job->shipping_address);
        $this->assertEquals(85000, (float) $job->estimated_charge);

        // Verify advance payment was recorded
        $this->assertDatabaseHas('service_job_payments', [
            'service_job_id' => $job->id,
            'method' => 'kpay',
            'amount' => 30000,
        ]);
        $this->assertEquals(30000, $job->paidAmount());
        $this->assertEquals(55000, $job->outstanding());
    }
}
