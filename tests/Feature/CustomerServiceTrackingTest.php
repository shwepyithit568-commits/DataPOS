<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use App\POS\Models\ServiceJobStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Alinn Thit Service Center',
            'slug' => 'alinn-thit-service',
            'address' => 'Yangon, Myanmar',
            'phone' => '09123456789',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['name' => 'Manager Tech']);
        $this->store->users()->attach($this->manager->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    public function test_customer_can_visit_service_tracking_lookup_page(): void
    {
        $response = $this->get('/service-tracking?store_slug=' . $this->store->slug);

        $response->assertStatus(200);
        $response->assertSee(__('messages.track_service_btn'));
    }

    public function test_customer_can_track_service_job_directly_via_token_without_login(): void
    {
        $job = ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => ServiceJob::generateNumber($this->store->id),
            'voucher_no' => 'V-9988',
            'contact_name' => 'Ko Aung Ko',
            'contact_phone' => '09777888999',
            'device_type' => 'CCTV Camera',
            'category' => 'CCTV Camera (ကင်မရာ)',
            'brand' => 'Dahua',
            'model' => 'DH-IPC-HFW1230S',
            'reported_problem' => 'Night vision IR LED not working',
            'status' => 'in_repair',
            'estimated_charge' => 25000,
            'created_by' => $this->manager->id,
        ]);

        $this->assertNotEmpty($job->tracking_token);

        // Status history
        ServiceJobStatus::create([
            'service_job_id' => $job->id,
            'status' => 'in_repair',
            'note' => 'Replacement IR sensor board ordered',
            'changed_by' => $this->manager->id,
        ]);

        // Unauthenticated guest request
        $response = $this->get("/store/{$this->store->slug}/track/service/{$job->tracking_token}");

        $response->assertStatus(200);
        $response->assertSee('V-9988');
        $response->assertSee($job->job_number);
        $response->assertSee('DH-IPC-HFW1230S');
        $response->assertSee('Ko Aung Ko');
        $response->assertSee('Night vision IR LED not working');
        $response->assertSee('25,000');
    }

    public function test_lookup_search_with_exact_match_redirects_to_token_url(): void
    {
        $job = ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => ServiceJob::generateNumber($this->store->id),
            'voucher_no' => 'V-4455',
            'contact_name' => 'Ma Thidar',
            'contact_phone' => '09111222333',
            'device_type' => 'Computer',
            'category' => 'Desktop / PC (ဒက်စတော့ပ်)',
            'brand' => 'Dell',
            'model' => 'OptiPlex 7090',
            'reported_problem' => 'No display power',
            'status' => 'diagnosing',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->get("/store/{$this->store->slug}/track/service?q=V-4455");

        $response->assertRedirect("/store/{$this->store->slug}/track/service/{$job->tracking_token}");
    }

    public function test_invalid_tracking_token_returns_404(): void
    {
        $response = $this->get("/store/{$this->store->slug}/track/service/non-existent-invalid-token-12345");

        $response->assertStatus(404);
    }
}
