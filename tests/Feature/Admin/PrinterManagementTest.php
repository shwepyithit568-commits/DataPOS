<?php

namespace Tests\Feature\Admin;

use App\Models\Printer;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\PrinterService;
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
            'slug' => 'test-hardware-store',
            'name' => 'Test Hardware Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_printers_index_renders_with_default_printer(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('Main POS Counter');
        $response->assertSee('80mm');
        $response->assertSee(__('messages.printers_title'));
    }

    public function test_create_network_ip_printer(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.printers.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Kitchen Bar LAN Printer',
                'connection_type' => 'network',
                'paper_width' => '80mm',
                'ip_address' => '192.168.1.220',
                'port' => 9100,
                'printer_role' => 'kitchen',
                'print_copies' => 2,
                'auto_cut' => 1,
                'cash_drawer_kick' => 0,
                'feed_lines' => 3,
                'header_text' => 'ORDER TICKET',
            ]);

        $response->assertRedirect(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('printers', [
            'store_id' => $this->store->id,
            'name' => 'Kitchen Bar LAN Printer',
            'connection_type' => 'network',
            'ip_address' => '192.168.1.220',
            'port' => 9100,
            'printer_role' => 'kitchen',
            'print_copies' => 2,
        ]);
    }

    public function test_create_bluetooth_printer(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.printers.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Portable PT-210 Mobile',
                'connection_type' => 'bluetooth',
                'paper_width' => '58mm',
                'device_path' => '00:11:22:33:44:55',
                'printer_role' => 'receipt',
                'print_copies' => 1,
                'auto_cut' => 0,
                'cash_drawer_kick' => 0,
                'feed_lines' => 1,
            ]);

        $response->assertRedirect(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('printers', [
            'store_id' => $this->store->id,
            'name' => 'Portable PT-210 Mobile',
            'connection_type' => 'bluetooth',
            'paper_width' => '58mm',
        ]);
    }

    public function test_update_printer_settings(): void
    {
        $service = app(PrinterService::class);
        $service->ensureDefaultPrinter($this->store);

        $printer = Printer::where('store_id', $this->store->id)->first();

        $response = $this->actingAs($this->manager)
            ->put(route('store.admin.printers.update', [
                'store_slug' => $this->store->slug,
                'printer' => $printer->id,
            ]), [
                'name' => 'Renamed Counter 80mm ESC/POS',
                'connection_type' => 'usb',
                'paper_width' => '80mm',
                'printer_role' => 'receipt',
                'device_path' => 'COM3',
                'print_copies' => 1,
                'auto_cut' => 1,
                'cash_drawer_kick' => 1,
                'feed_lines' => 4,
            ]);

        $response->assertRedirect(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $printer->refresh();
        $this->assertEquals('Renamed Counter 80mm ESC/POS', $printer->name);
        $this->assertEquals('usb', $printer->connection_type);
        $this->assertEquals('COM3', $printer->device_path);
        $this->assertEquals(4, $printer->feed_lines);
    }

    public function test_set_default_printer(): void
    {
        $service = app(PrinterService::class);
        $service->ensureDefaultPrinter($this->store);

        $printer1 = Printer::where('store_id', $this->store->id)->first();
        $printer2 = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'Second Printer',
            'connection_type' => 'network',
            'paper_width' => '80mm',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->assertTrue($printer1->fresh()->is_default);
        $this->assertFalse($printer2->fresh()->is_default);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.printers.set_default', [
                'store_slug' => $this->store->slug,
                'printer' => $printer2->id,
            ]));

        $response->assertRedirect(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertFalse($printer1->fresh()->is_default);
        $this->assertTrue($printer2->fresh()->is_default);
    }

    public function test_delete_non_default_printer(): void
    {
        $service = app(PrinterService::class);
        $service->ensureDefaultPrinter($this->store);

        $printer2 = Printer::create([
            'store_id' => $this->store->id,
            'name' => 'To Delete Printer',
            'connection_type' => 'network',
            'paper_width' => '80mm',
            'is_default' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)
            ->delete(route('store.admin.printers.destroy', [
                'store_slug' => $this->store->slug,
                'printer' => $printer2->id,
            ]));

        $response->assertRedirect(route('store.admin.printers.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('printers', ['id' => $printer2->id]);
    }

    public function test_test_print_view_renders_successfully(): void
    {
        $service = app(PrinterService::class);
        $service->ensureDefaultPrinter($this->store);

        $printer = Printer::where('store_id', $this->store->id)->first();

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.printers.test_print', [
                'store_slug' => $this->store->slug,
                'printer' => $printer->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('TEST PRINT');
        $response->assertSee($printer->name);
        $response->assertSee('HARDWARE OK');
    }
}
