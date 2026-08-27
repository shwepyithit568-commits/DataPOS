<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceJobsTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Store B',
            'slug' => 'uat-store-b',
            'currency' => 'MMK',
        ]);

        $this->manager = User::create([
            'name' => 'Store Manager',
            'phone' => '09112233445',
            'password' => bcrypt('password123'),
        ]);

        $this->store->users()->attach($this->manager->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);
    }

    public function test_service_jobs_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/service-jobs");

        $response->assertStatus(200);
        $response->assertSeeText($this->store->name);
    }

    public function test_service_jobs_create_renders_successfully(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/service-jobs/create");

        $response->assertStatus(200);
    }

    public function test_service_jobs_quick_add_technician(): void
    {
        $response = $this->actingAs($this->manager)
            ->postJson("/store/{$this->store->slug}/admin/service-jobs/quick-add-technician", [
                'name' => 'Ko Aung Technician',
                'phone' => '09445566778',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'technician' => [
                'name' => 'Ko Aung Technician',
                'phone' => '09445566778',
            ],
        ]);

        $user = User::where('phone', '09445566778')->first();
        $this->assertNotNull($user);
        $this->assertTrue($this->store->users()->where('users.id', $user->id)->exists());
    }

    public function test_service_jobs_index_renders_in_all_supported_locales_without_key_leaks(): void
    {
        $job = ServiceJob::create([
            'store_id' => $this->store->id,
            'created_by' => $this->manager->id,
            'job_number' => 'SVC-20260828-0001',
            'contact_name' => 'Daw Mya',
            'contact_phone' => '09123456789',
            'device_type' => 'Computer',
            'brand' => 'Dell',
            'model' => 'OptiPlex 7090',
            'reported_problem' => 'Power supply issue',
            'status' => 'received',
            'estimated_charge' => 45000,
            'advance_payment' => 10000,
            'total_amount' => 45000,
            'paid_amount' => 10000,
        ]);

        foreach (['en', 'my', 'zh'] as $locale) {
            app()->setLocale($locale);
            $response = $this->actingAs($this->manager)
                ->get("/store/{$this->store->slug}/admin/service-jobs?lang={$locale}");

            $response->assertStatus(200);
            $content = $response->getContent();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $content),
                "Found leaked translation key in locale [{$locale}] on admin/service-jobs"
            );
        }
    }

    public function test_service_jobs_export_csv(): void
    {
        ServiceJob::create([
            'store_id' => $this->store->id,
            'created_by' => $this->manager->id,
            'job_number' => 'SVC-20260828-0001',
            'contact_name' => 'Daw Mya',
            'contact_phone' => '09123456789',
            'device_type' => 'Computer',
            'brand' => 'Dell',
            'model' => 'OptiPlex 7090',
            'reported_problem' => 'Power supply issue',
            'status' => 'received',
            'estimated_charge' => 45000,
            'advance_payment' => 10000,
            'total_amount' => 45000,
            'paid_amount' => 10000,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/service-jobs/export");

        $response->assertStatus(200);
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response->baseResponse);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
