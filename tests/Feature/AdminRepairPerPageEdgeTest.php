<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Index edge cases: hand-edited per_page values must never crash the page.
 * A negative per-page previously threw InvalidArgumentException (HTTP 500)
 * from Laravel's paginate().
 */
class AdminRepairPerPageEdgeTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create(['name' => 'Store One', 'slug' => 'store-one']);
        $this->store->setting()->create(['store_name' => 'Store One', 'default_language' => 'en']);

        $this->manager = User::factory()->create(['phone' => '09111111111']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);
    }

    private function seedJob(): void
    {
        ServiceJob::create([
            'store_id' => $this->store->id,
            'job_number' => ServiceJob::generateNumber($this->store->id),
            'contact_name' => 'Walk In',
            'device_type' => 'Phone',
            'reported_problem' => 'Test',
            'status' => 'received',
            'created_by' => $this->manager->id,
        ]);
    }

    public function test_negative_per_page_does_not_crash(): void
    {
        $this->seedJob();

        // Previously: InvalidArgumentException → HTTP 500.
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?per_page=-5");

        $response->assertStatus(200);
        $response->assertSeeText('Repair Center');
    }

    public function test_non_numeric_per_page_falls_back(): void
    {
        $this->seedJob();

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?per_page=abc");

        $response->assertStatus(200);
        $response->assertSeeText('Repair Center');
    }

    public function test_zero_per_page_does_not_crash(): void
    {
        $this->seedJob();

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?per_page=0");

        $response->assertStatus(200);
    }

    public function test_float_per_page_is_truncated(): void
    {
        $this->seedJob();

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/repairs?per_page=2.7");

        $response->assertStatus(200);
        // (int)'2.7' = 2 → the page still renders with a sane page size.
        $response->assertSeeText('Repair Center');
    }
}
