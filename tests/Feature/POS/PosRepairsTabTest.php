<?php

namespace Tests\Feature\POS;

use App\BusinessProfiles\BusinessProfile;
use App\Capabilities\Capability;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\ServiceJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PosRepairsTabTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(array $attributes = []): Store
    {
        $slug = 'test-pos-' . Str::random(6);

        $defaults = [
            'name'             => ucfirst($slug),
            'slug'             => $slug,
            'business_profile' => BusinessProfile::REPAIR_SERVICE,
            'operation_mode'   => BusinessProfile::MODE_OMNICHANNEL,
            'is_active'        => true,
        ];

        $store = Store::create(array_merge($defaults, $attributes));
        $store->setting()->create([
            'store_name'       => $store->name,
            'default_language' => 'en',
        ]);

        return $store;
    }

    private function staff(Store $store): User
    {
        $user = User::create([
            'name'     => 'Cashier Staff ' . Str::random(4),
            'phone'    => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role'     => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => 'staff', 'status' => 'active']);

        return $user;
    }

    public function test_pos_page_renders_repairs_tab_for_store_with_service_capability(): void
    {
        $store = $this->makeStore([
            'capabilities_override' => [Capability::SERVICE_REPAIR_JOBS => true],
        ]);
        $staff = $this->staff($store);

        $job = ServiceJob::create([
            'store_id'         => $store->id,
            'job_number'       => 'SVC-20260911-0001',
            'voucher_no'       => 'REP-101',
            'contact_name'     => 'U Ba',
            'contact_phone'    => '0912345678',
            'device_type'      => 'Laptop',
            'brand'            => 'Dell',
            'model'            => 'XPS 15',
            'reported_problem' => 'Screen flickering',
            'status'           => 'in_repair',
            'estimated_charge' => 150000,
            'final_charge'     => 150000,
            'created_by'       => $staff->id,
        ]);

        $response = $this->actingAs($staff)->get("/store/{$store->slug}/pos");

        $response->assertOk();
        $response->assertSee('admin/repairs');
        $response->assertSee('SVC-20260911-0001');
        $response->assertSee('REP-101');
        $response->assertSee('U Ba');
        $response->assertSee('Screen flickering');
        $response->assertSee(__('messages.pos_tab_repairs'));
    }

    public function test_pos_page_does_not_render_repairs_tab_when_capability_disabled(): void
    {
        $store = $this->makeStore([
            'business_profile'      => BusinessProfile::GENERAL_RETAIL,
            'capabilities_override' => [Capability::SERVICE_REPAIR_JOBS => false],
        ]);
        $staff = $this->staff($store);

        $response = $this->actingAs($staff)->get("/store/{$store->slug}/pos");

        $response->assertOk();
        $response->assertDontSee(__('messages.pos_tab_repairs'));
        $response->assertDontSee("activeTab === 'repairs'");
    }

    public function test_pos_repairs_tab_renders_without_key_leaks_in_all_locales(): void
    {
        $store = $this->makeStore([
            'capabilities_override' => [Capability::SERVICE_REPAIR_JOBS => true],
        ]);
        $staff = $this->staff($store);

        ServiceJob::create([
            'store_id'         => $store->id,
            'job_number'       => 'SVC-20260911-0002',
            'contact_name'     => 'Daw Hla',
            'device_type'      => 'Phone',
            'reported_problem' => 'Battery replacement',
            'status'           => 'ready',
            'estimated_charge' => 50000,
            'created_by'       => $staff->id,
        ]);

        foreach (['en', 'my', 'zh_CN'] as $locale) {
            app()->setLocale($locale);

            $response = $this->actingAs($staff)->get("/store/{$store->slug}/pos");

            $response->assertOk();
            $response->assertDontSee('messages.pos_tab_repairs');
            $response->assertDontSee('messages.pos_repairs_quick_nav');
            $response->assertDontSee('messages.pos_view_all_repairs');
            $response->assertDontSee('messages.pos_no_active_repairs');
            $response->assertSee(__('messages.pos_tab_repairs'));
        }
    }
}
