<?php

namespace Tests\Feature\POS;

use App\Models\Printer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\CashierShiftService;
use App\POS\Services\InventoryService;
use App\POS\Services\PosSaleService;
use App\Services\HardwareMatrixService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class HardwareCompatibilityAndDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private PosSaleService $sales;
    private InventoryService $inventory;
    private CashierShiftService $shifts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sales = app(PosSaleService::class);
        $this->inventory = app(InventoryService::class);
        $this->shifts = app(CashierShiftService::class);
    }

    private function makeStore(string $slug = 'hardware-test-store'): Store
    {
        return Store::create([
            'name' => 'Tech & Mobile Hub',
            'slug' => $slug,
            'is_active' => true,
            'currency' => 'MMK',
        ]);
    }

    private function staff(Store $store, string $role = 'store_owner'): User
    {
        $user = User::create([
            'name' => 'Technician ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    private function makeProduct(Store $store, array $overrides = []): Product
    {
        $name = $overrides['name'] ?? 'Item ' . Str::random(4);

        return Product::create(array_merge([
            'store_id' => $store->id,
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'slug' => Str::slug($name . '-' . Str::random(3)),
            'retail_price' => 25000,
            'wholesale_price' => 20000,
        ], $overrides));
    }

    private function seedStock(Store $store, Product $product, string $qty = '10', string $cost = '15000'): void
    {
        $this->inventory->postMovement([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'movement_type' => 'opening_balance',
            'quantity_delta' => $qty,
            'unit_cost' => $cost,
            'source_type' => 'opening_balance',
            'client_transaction_id' => 'seed:' . Str::uuid(),
            'occurred_at' => now(),
        ]);
    }

    /**
     * §15 Hardware Compatibility:
     * 58mm and 80mm ESC/POS test receipts must format to exact character widths,
     * emit ESC/POS initialization, store header, alignment markers, barcode, and auto-cut commands.
     */
    public function test_hardware_matrix_generates_58mm_and_80mm_escpos_test_receipts_with_cut_command(): void
    {
        // 1. Test 58mm profile (32 chars/column)
        $receipt58 = HardwareMatrixService::generateEscPosTestReceipt('58mm', 'Mobile Store 58');
        $this->assertStringContainsString("\x1B@", $receipt58); // Init command
        $this->assertStringContainsString('Mobile Store 58', $receipt58);
        $this->assertStringContainsString('58MM ESC/POS', $receipt58);
        $this->assertStringContainsString("\x1D\x56\x41\x00", $receipt58); // Full cut command
        $this->assertStringContainsString("\x1D\x6B\x04", $receipt58); // Barcode test

        // 2. Test 80mm profile (48 chars/column)
        $receipt80 = HardwareMatrixService::generateEscPosTestReceipt('80mm', 'Mobile Store 80');
        $this->assertStringContainsString("\x1B@", $receipt80);
        $this->assertStringContainsString('Mobile Store 80', $receipt80);
        $this->assertStringContainsString('80MM ESC/POS', $receipt80);
        $this->assertStringContainsString("\x1D\x56\x41\x00", $receipt80);
    }

    /**
     * §15 Hardware Compatibility:
     * Cash drawer kick pulse must emit standard ESC p m t1 t2 byte pulse.
     */
    public function test_cash_drawer_kick_command_generation(): void
    {
        // Pin 0 (standard drawer pin 2): ESC p \x00 \x19 \xFA
        $pulsePin0 = HardwareMatrixService::generateCashDrawerKickCommand(0);
        $this->assertSame("\x1B\x70\x00\x19\xFA", $pulsePin0);

        // Pin 1 (secondary drawer pin 5): ESC p \x01 \x19 \xFA
        $pulsePin1 = HardwareMatrixService::generateCashDrawerKickCommand(1);
        $this->assertSame("\x1B\x70\x01\x19\xFA", $pulsePin1);
    }

    /**
     * §15 Hardware Compatibility:
     * Live posted POS sale generates full ESC/POS receipt stream with drawer kick,
     * invoice number, item lines, total, payment details, barcode, and auto-cut.
     */
    public function test_escpos_sale_receipt_generates_full_pos_receipt_with_drawer_kick_and_barcode(): void
    {
        $store = $this->makeStore();
        $cashier = $this->staff($store);
        $shift = $this->shifts->openShift($store, ['register_name' => 'REG-HDW', 'opening_cash' => 50000], $cashier);

        $product = $this->makeProduct($store, ['name' => 'Tempered Glass Pro', 'retail_price' => 15000]);
        $this->seedStock($store, $product, '10');

        $this->sales->addToCart($store, $product->id, null, '2');
        $sale = $this->sales->post($store, $this->sales->cartLines($store), [
            ['method' => 'cash', 'amount' => '30000', 'reference' => 'CASH-PAY-01'],
        ], $cashier, $shift);

        $escposStream = HardwareMatrixService::generateEscPosSaleReceipt($sale, '80mm', true);

        // Assert presence of critical byte commands and text
        $this->assertStringContainsString("\x1B\x70\x00\x19\xFA", $escposStream); // Cash drawer kick pulse
        $this->assertStringContainsString("\x1B@", $escposStream); // Printer init
        $this->assertStringContainsString($sale->receipt_number, $escposStream);
        $this->assertStringContainsString('Tempered Glass Pro', $escposStream);
        $this->assertStringContainsString('30,000 MMK', $escposStream);
        $this->assertStringContainsString('Paid (CASH):', $escposStream);
        $this->assertStringContainsString("\x1D\x6B\x04", $escposStream); // Barcode command
        $this->assertStringContainsString("\x1D\x56\x41\x00", $escposStream); // Auto-cut
    }

    /**
     * §15 Self-Service Diagnostic UAT:
     * Staff can view thermal test receipt and download raw ESC/POS binary file.
     */
    public function test_printer_controller_serves_test_print_and_escpos_bin_download(): void
    {
        $store = $this->makeStore();
        $staff = $this->staff($store);

        $printer = Printer::create([
            'store_id' => $store->id,
            'name' => 'Main POS Thermal 80mm',
            'paper_width' => '80mm',
            'connection_type' => 'browser',
            'printer_role' => 'receipt',
            'auto_cut' => true,
            'cash_drawer_kick' => true,
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->actingAs($staff);

        // 1. Render test print view
        $testPrintResponse = $this->get("/store/{$store->slug}/admin/printers/{$printer->id}/test-print");
        $testPrintResponse->assertOk();
        $testPrintResponse->assertSee('Main POS Thermal 80mm');
        $testPrintResponse->assertSee('80mm');
        $testPrintResponse->assertSee('Barcode Scanner Diagnostic', false);
        $testPrintResponse->assertSee('Download ESC/POS .bin');

        // 2. Download raw ESC/POS binary stream
        $binResponse = $this->get("/store/{$store->slug}/admin/printers/{$printer->id}/escpos-bin");
        $binResponse->assertOk();
        $this->assertSame('application/octet-stream', $binResponse->headers->get('content-type'));
        $this->assertStringContainsString('attachment; filename="escpos_test_80mm.bin"', $binResponse->headers->get('content-disposition'));
        $this->assertStringContainsString("\x1B\x70\x00\x19\xFA", $binResponse->getContent());
        $this->assertStringContainsString("\x1D\x56\x41\x00", $binResponse->getContent());
    }
}
