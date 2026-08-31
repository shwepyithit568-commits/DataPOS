<?php

namespace Tests\Feature\Admin;

use App\Models\Printer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrinterManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Printer Test Store',
            'slug' => 'printer-test-store',
            'is_active' => true,
        ]);
        $this->store->setting()->create([
            'store_name' => 'Printer Test Store',
            'default_language' => 'my',
        ]);

        $this->manager = User::factory()->create(['name' => 'Manager U Ba', 'phone' => '09111222333']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager', 'status' => 'active']);

        $this->staff = User::factory()->create(['name' => 'Staff Ko Lay', 'phone' => '09444555666']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff', 'status' => 'active']);
    }

    public function test_manager_can_view_printers_index(): void
    {
        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/printers");

        $response->assertOk();
    }

    public function test_manager_can_create_printer(): void
    {
        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/printers", [
                'name' => 'Main Counter 80mm',
                'connection_type' => 'network',
                'paper_width' => '80mm',
                'printer_role' => 'receipt',
                'ip_address' => '192.168.1.200',
                'port' => 9100,
                'is_default' => 1,
                'auto_cut' => 1,
                'cash_drawer_kick' => 1,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('printers', [
            'store_id' => $this->store->id,
            'name' => 'Main Counter 80mm',
            'paper_width' => '80mm',
            'ip_address' => '192.168.1.200',
        ]);
    }

    public function test_manager_can_update_printer(): void
    {
        $printer = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Old Printer',
            'connection_type' => 'usb',
            'paper_width' => '58mm',
            'printer_role' => 'receipt',
        ]);

        $response = $this->actingAs($this->manager)
            ->put("/store/{$this->store->slug}/admin/printers/{$printer->id}", [
                'name' => 'Updated 58mm',
                'connection_type' => 'usb',
                'paper_width' => '58mm',
                'printer_role' => 'receipt',
                'is_default' => 0,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('printers', [
            'id' => $printer->id,
            'name' => 'Updated 58mm',
        ]);
    }

    public function test_manager_can_set_default_printer(): void
    {
        $printer1 = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Printer 1',
            'connection_type' => 'usb',
            'paper_width' => 80,
            'is_default' => true,
        ]);

        $printer2 = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Printer 2',
            'connection_type' => 'network',
            'paper_width' => 80,
            'is_default' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->post("/store/{$this->store->slug}/admin/printers/{$printer2->id}/set-default");

        $response->assertRedirect();
        $this->assertTrue($printer2->fresh()->is_default);
        $this->assertFalse($printer1->fresh()->is_default);
    }

    public function test_manager_can_view_test_print(): void
    {
        $printer = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Test 80mm',
            'connection_type' => 'network',
            'paper_width' => 80,
        ]);

        $response = $this->actingAs($this->manager)
            ->get("/store/{$this->store->slug}/admin/printers/{$printer->id}/test-print");

        $response->assertOk();
    }

    public function test_manager_can_delete_printer(): void
    {
        $printer = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Delete Me',
            'connection_type' => 'bluetooth',
            'paper_width' => 58,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete("/store/{$this->store->slug}/admin/printers/{$printer->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('printers', [
            'id' => $printer->id,
        ]);
    }

    public function test_printers_views_render_without_translation_leaks_in_all_locales(): void
    {
        $printer = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Flagship 80mm ESC/POS',
            'connection_type' => 'network',
            'paper_width' => '80mm',
            'printer_role' => 'receipt',
            'ip_address' => '192.168.1.200',
            'port' => 9100,
            'auto_cut' => 1,
            'cash_drawer_kick' => 1,
            'print_logo' => 1,
            'is_default' => 1,
            'is_active' => 1,
        ]);

        foreach (['en', 'my', 'zh_CN'] as $locale) {
            // Test Index View
            $indexResponse = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));

            $indexResponse->assertOk();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $indexResponse->getContent()),
                "Found leaked translation key in locale [{$locale}] on printers.index"
            );

            // Test Create View
            $createResponse = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.printers.create', ['store_slug' => $this->store->slug]));

            $createResponse->assertOk();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $createResponse->getContent()),
                "Found leaked translation key in locale [{$locale}] on printers.create"
            );

            // Test Edit View
            $editResponse = $this->actingAs($this->manager)
                ->withSession(['locale' => $locale])
                ->get(route('store.admin.printers.edit', [
                    'store_slug' => $this->store->slug,
                    'printer' => $printer->id,
                ]));

            $editResponse->assertOk();
            $this->assertFalse(
                (bool) preg_match('/messages\.[a-zA-Z0-9_-]+/', $editResponse->getContent()),
                "Found leaked translation key in locale [{$locale}] on printers.edit"
            );
        }
    }
}
