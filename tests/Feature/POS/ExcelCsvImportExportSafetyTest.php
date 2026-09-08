<?php

namespace Tests\Feature\POS;

use App\Models\AuditLog;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\Branch;
use App\POS\Models\CashierShift;
use App\POS\Models\PosPayment;
use App\POS\Models\PosSale;
use App\POS\Models\PosSaleItem;
use App\Services\ExportDataSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class ExcelCsvImportExportSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'export-store'): Store
    {
        $store = Store::create([
            'name' => 'Tech Hub Yangon',
            'slug' => $slug,
            'is_active' => true,
        ]);

        Branch::create([
            'store_id' => $store->id,
            'name' => 'Main Branch',
            'code' => 'MAIN',
            'is_default' => true,
            'is_active' => true,
        ]);

        return $store;
    }

    private function makeUser(Store $store, string $role = 'staff'): User
    {
        $user = User::create([
            'name' => ucfirst($role) . ' ' . Str::random(4),
            'phone' => '09' . rand(10000000, 99999999),
            'password' => bcrypt('password'),
            'role' => $role === 'owner' ? 'admin' : 'customer',
        ]);
        $user->stores()->attach($store->id, ['role' => $role, 'status' => 'active']);

        return $user;
    }

    public function test_formula_injection_sanitization_neutralizes_dangerous_prefixes(): void
    {
        // Equal sign formula
        $this->assertEquals("'=cmd|' /C calc'!A0", ExportDataSanitizer::sanitizeCsvValue("=cmd|' /C calc'!A0"));

        // Plus sign formula
        $this->assertEquals("'+1+2", ExportDataSanitizer::sanitizeCsvValue("+1+2"));

        // At sign formula
        $this->assertEquals("'@SUM(1,2)", ExportDataSanitizer::sanitizeCsvValue("@SUM(1,2)"));

        // Negative non-numeric string
        $this->assertEquals("'-malicious", ExportDataSanitizer::sanitizeCsvValue("-malicious"));

        // Valid negative number should remain unchanged
        $this->assertEquals("-1500.50", ExportDataSanitizer::sanitizeCsvValue("-1500.50"));

        // Regular safe text should remain untouched
        $this->assertEquals("Normal Product Name", ExportDataSanitizer::sanitizeCsvValue("Normal Product Name"));
    }

    public function test_sanitize_csv_row_sanitizes_array_elements(): void
    {
        $row = [
            'REC-001',
            '=HYPERLINK("http://evil.com")',
            '09420000000',
            -500,
        ];

        $sanitized = ExportDataSanitizer::sanitizeCsvRow($row);

        $this->assertEquals('REC-001', $sanitized[0]);
        $this->assertEquals('\'=HYPERLINK("http://evil.com")', $sanitized[1]);
        $this->assertEquals('09420000000', $sanitized[2]);
        $this->assertEquals('-500', $sanitized[3]);
    }

    public function test_set_string_cell_preserves_leading_zeros_in_phpspreadsheet(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Preserves phone number leading zero
        ExportDataSanitizer::setStringCell($sheet, 'A1', '09450012345');
        // Preserves barcode leading zeroes
        ExportDataSanitizer::setStringCell($sheet, 'A2', '000012345678');

        $cellA1 = $sheet->getCell('A1');
        $cellA2 = $sheet->getCell('A2');

        $this->assertEquals(DataType::TYPE_STRING, $cellA1->getDataType());
        $this->assertEquals('09450012345', $cellA1->getValue());

        $this->assertEquals(DataType::TYPE_STRING, $cellA2->getDataType());
        $this->assertEquals('000012345678', $cellA2->getValue());
    }

    public function test_utf8_bom_returns_expected_byte_sequence(): void
    {
        $bom = ExportDataSanitizer::utf8Bom();
        $this->assertEquals("\xEF\xBB\xBF", $bom);
    }

    public function test_export_audit_creates_audit_log_entry(): void
    {
        $store = $this->makeStore();
        $user = $this->makeUser($store, 'owner');

        ExportDataSanitizer::auditExport($store, 'sales_report', $user, [
            'format' => 'csv',
            'records' => 42,
        ]);

        $audit = AuditLog::where('store_id', $store->id)
            ->where('action', 'export.sales_report')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('export', $audit->entity_type);
        $this->assertEquals($user->id, $audit->actor_id);
        $this->assertEquals('csv', $audit->metadata['format']);
        $this->assertEquals(42, $audit->metadata['records']);
    }

    public function test_pos_sales_csv_export_includes_utf8_bom_and_records_audit(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');

        $shift = CashierShift::create([
            'store_id' => $store->id,
            'cashier_id' => $cashier->id,
            'register_name' => 'POS-01',
            'opening_cash' => 50000,
            'opened_at' => now(),
            'status' => 'open',
        ]);

        $sale = PosSale::create([
            'store_id' => $store->id,
            'cashier_shift_id' => $shift->id,
            'created_by' => $cashier->id,
            'cashier_id' => $cashier->id,
            'receipt_number' => '=DANGEROUS-VOUCHER',
            'invoice_no' => 'INV-001',
            'subtotal' => 10000,
            'discount' => 0,
            'tax' => 0,
            'total' => 10000,
            'status' => 'completed',
            'posted_at' => now(),
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 10000,
            'change_given' => 0,
        ]);

        $response = $this->actingAs($cashier)->get(
            route('pos.reports.sales.export', [
                'store_slug' => $store->slug,
                'format' => 'csv',
            ])
        );

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Check UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);

        // Check sanitized voucher formula
        $this->assertStringContainsString("'=DANGEROUS-VOUCHER", $content);

        // Check AuditLog written
        $audit = AuditLog::where('store_id', $store->id)
            ->where('action', 'export.sales_report')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals('csv', $audit->metadata['format']);
    }
}
