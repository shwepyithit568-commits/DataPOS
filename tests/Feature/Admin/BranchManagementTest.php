<?php

namespace Tests\Feature\Admin;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use App\POS\Services\StoreLocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-branch-store',
            'name' => 'Test Branch Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_branches_index_renders_with_default_branch(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.branches.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('Main Branch');
        $response->assertSee(__('messages.branches_title'));
    }

    public function test_create_new_branch_with_auto_warehouse(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.branches.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Mandalay 78th Branch',
                'code' => 'MDY-01',
                'phone' => '09-45001122',
                'address' => '78th Street, Mandalay',
                'manager_name' => 'Ko Kyaw',
                'notes' => 'Flagship showroom in upper Myanmar',
                'create_warehouse' => 1,
                'is_default' => 0,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.branches.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('branches', [
            'store_id' => $this->store->id,
            'name' => 'Mandalay 78th Branch',
            'code' => 'MDY-01',
            'manager_name' => 'Ko Kyaw',
        ]);

        $this->assertDatabaseHas('warehouses', [
            'store_id' => $this->store->id,
            'name' => 'Mandalay 78th Branch Warehouse',
            'code' => 'MDY-01-WH',
        ]);
    }

    public function test_update_branch_details(): void
    {
        $service = app(StoreLocationService::class);
        $service->ensureDefaults($this->store);

        $branch = Branch::where('store_id', $this->store->id)->first();

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.branches.update', [
                'store_slug' => $this->store->slug,
                'branch' => $branch->id,
            ]), [
                'name' => 'Renamed Main Flagship',
                'code' => 'MAIN-01',
                'phone' => '09-111222333',
                'address' => 'Bogyoke Road, Yangon',
                'manager_name' => 'Daw Mya',
                'notes' => 'Updated flagship notes',
                'is_default' => 1,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('store.admin.branches.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $branch->refresh();
        $this->assertEquals('Renamed Main Flagship', $branch->name);
        $this->assertEquals('MAIN-01', $branch->code);
        $this->assertEquals('Daw Mya', $branch->manager_name);
    }

    public function test_set_default_branch(): void
    {
        $service = app(StoreLocationService::class);
        $service->ensureDefaults($this->store);

        $branch1 = Branch::where('store_id', $this->store->id)->first();
        $branch2 = Branch::create([
            'store_id' => $this->store->id,
            'name' => 'Branch 2',
            'code' => 'BR2',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($branch1->fresh()->is_default);
        $this->assertFalse($branch2->fresh()->is_default);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.branches.set_default', [
                'store_slug' => $this->store->slug,
                'branch' => $branch2->id,
            ]));

        $response->assertRedirect(route('store.admin.branches.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertFalse($branch1->fresh()->is_default);
        $this->assertTrue($branch2->fresh()->is_default);
    }

    public function test_cannot_delete_default_branch(): void
    {
        $service = app(StoreLocationService::class);
        $service->ensureDefaults($this->store);

        $branch = Branch::where('store_id', $this->store->id)->where('is_default', true)->first();

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.branches.destroy', [
                'store_slug' => $this->store->slug,
                'branch' => $branch->id,
            ]));

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }

    public function test_delete_non_default_branch(): void
    {
        $service = app(StoreLocationService::class);
        $service->ensureDefaults($this->store);

        $branch2 = Branch::create([
            'store_id' => $this->store->id,
            'name' => 'Branch To Delete',
            'code' => 'DEL',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.branches.destroy', [
                'store_slug' => $this->store->slug,
                'branch' => $branch2->id,
            ]));

        $response->assertRedirect(route('store.admin.branches.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('branches', ['id' => $branch2->id]);
    }

    public function test_show_branch_details_view(): void
    {
        $service = app(StoreLocationService::class);
        $service->ensureDefaults($this->store);

        $branch = Branch::where('store_id', $this->store->id)->first();

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.branches.show', [
                'store_slug' => $this->store->slug,
                'branch' => $branch->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($branch->name);
        $response->assertSee('Linked Warehouses');
    }
}
