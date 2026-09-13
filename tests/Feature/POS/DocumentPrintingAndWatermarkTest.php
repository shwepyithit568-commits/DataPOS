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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentPrintingAndWatermarkTest extends TestCase
{
    use RefreshDatabase;

    private function makeStore(string $slug = 'doc-store'): Store
    {
        $store = Store::create([
            'name' => 'Diamond Tech POS',
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

    private function makeSale(Store $store, User $cashier, string $status = 'posted'): PosSale
    {
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
            'receipt_number' => 'REC-' . strtoupper(Str::random(6)),
            'invoice_no' => 'INV-' . strtoupper(Str::random(6)),
            'subtotal' => 15000,
            'discount' => 0,
            'tax' => 0,
            'total' => 15000,
            'status' => $status,
            'print_count' => 0,
            'posted_at' => now(),
        ]);

        PosSaleItem::create([
            'pos_sale_id' => $sale->id,
            'product_name' => 'Remax USB Cable',
            'quantity' => 2,
            'unit_price' => 7500,
            'line_total' => 15000,
        ]);

        PosPayment::create([
            'pos_sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 15000,
            'change_given' => 5000,
        ]);

        return $sale;
    }

    public function test_receipt_preview_does_not_increment_print_count(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $sale = $this->makeSale($store, $cashier);

        $response = $this->actingAs($cashier)->get(
            route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id])
        );

        $response->assertStatus(200);
        $response->assertSee($sale->receipt_number);
        $response->assertSee('Diamond Tech POS');
        $response->assertSee('Remax USB Cable');
        // Original preview keeps the reprint note hidden
        $response->assertViewHas('isReprint', false);

        // Opening or refreshing a preview must not be recorded as a print.
        $audit = AuditLog::where('store_id', $store->id)
            ->where('action', 'pos_receipt_printed')
            ->where('entity_id', $sale->id)
            ->first();
        $this->assertNull($audit);
        $this->get(route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id]))->assertOk();
        $this->assertSame(0, AuditLog::whereIn('action', ['pos_receipt_printed', 'pos_receipt_reprinted'])->count());
    }

    public function test_subsequent_receipt_print_renders_reprint_watermark_and_logs_audit(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $sale = $this->makeSale($store, $cashier);

        // Explicit first print request, then a read-only preview of the next copy.
        $this->actingAs($cashier)->postJson(
            route('pos.receipt.print_request', ['store_slug' => $store->slug, 'sale' => $sale->id])
        )->assertOk()->assertJson(['print_count' => 1, 'is_reprint' => false]);

        // 2nd print
        $response = $this->actingAs($cashier)->get(
            route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id])
        );

        $response->assertStatus(200);

        // Shows reprint watermark element and count
        $response->assertViewHas('isReprint', true);
        $response->assertSee('id="receiptPrintCount">2</span>', false);

        $this->postJson(route('pos.receipt.print_request', ['store_slug' => $store->slug, 'sale' => $sale->id]))
            ->assertOk()->assertJson(['print_count' => 2, 'is_reprint' => true]);

        // Verify AuditLog entry for reprint
        $audit = AuditLog::where('store_id', $store->id)
            ->where('action', 'pos_receipt_reprinted')
            ->where('entity_id', $sale->id)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame('print_requested', $audit->metadata['event']);
    }

    public function test_print_request_rejects_cross_store_and_unposted_sales(): void
    {
        $store = $this->makeStore();
        $otherStore = $this->makeStore('other-doc-store');
        $cashier = $this->makeUser($store, 'staff');
        $otherCashier = $this->makeUser($otherStore, 'staff');
        $sale = $this->makeSale($store, $cashier);

        $this->actingAs($otherCashier)->postJson(route('pos.receipt.print_request', [
            'store_slug' => $otherStore->slug, 'sale' => $sale->id,
        ]))->assertNotFound();
        $sale->update(['status' => 'draft']);
        $this->actingAs($cashier)->postJson(route('pos.receipt.print_request', [
            'store_slug' => $store->slug, 'sale' => $sale->id,
        ]))->assertNotFound();
        $this->assertSame(0, AuditLog::whereIn('action', ['pos_receipt_printed', 'pos_receipt_reprinted'])->count());
    }

    public function test_print_request_requires_staff_access(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $customer = $this->makeUser($store, 'retail_customer');
        $sale = $this->makeSale($store, $cashier);
        $url = route('pos.receipt.print_request', ['store_slug' => $store->slug, 'sale' => $sale->id]);

        $this->postJson($url)->assertUnauthorized();
        $this->actingAs($customer)->postJson($url)->assertForbidden();
        $this->assertSame(0, AuditLog::whereIn('action', ['pos_receipt_printed', 'pos_receipt_reprinted'])->count());
    }

    public function test_print_request_requires_csrf_token(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $sale = $this->makeSale($store, $cashier);
        $url = route('pos.receipt.print_request', ['store_slug' => $store->slug, 'sale' => $sale->id]);
        // Laravel normally bypasses CSRF in tests; enable the real middleware check.
        $this->app->instance('env', 'local');
        $this->actingAs($cashier)->postJson($url)->assertStatus(419);
        $this->assertSame(0, AuditLog::whereIn('action', ['pos_receipt_printed', 'pos_receipt_reprinted'])->count());
        $this->withSession(['_token' => 'receipt-csrf'])->postJson($url, [], [
            'X-CSRF-TOKEN' => 'receipt-csrf',
        ])->assertOk()->assertJson(['print_count' => 1]);
    }

    public function test_voided_or_cancelled_sale_receipt_renders_prominent_void_watermark(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $sale = $this->makeSale($store, $cashier, status: 'voided');

        $this->assertTrue($sale->isVoided());

        $response = $this->actingAs($cashier)->get(
            route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id])
        );

        $response->assertStatus(200);
        $response->assertSee('void-banner');
        $response->assertSee('VOID');
    }

    public function test_cancelled_sale_status_is_treated_as_voided(): void
    {
        $store = $this->makeStore();
        $cashier = $this->makeUser($store, 'staff');
        $sale = $this->makeSale($store, $cashier, status: 'cancelled');

        $this->assertTrue($sale->isVoided());

        $response = $this->actingAs($cashier)->get(
            route('pos.receipt', ['store_slug' => $store->slug, 'sale' => $sale->id])
        );

        $response->assertStatus(200);
        $response->assertSee('void-banner');
    }

    public function test_documents_settings_route_alias_redirects_to_voucher_customizer(): void
    {
        $store = $this->makeStore();
        $manager = $this->makeUser($store, 'store_manager');

        $response = $this->actingAs($manager)->get(
            route('store.admin.settings.documents', ['store_slug' => $store->slug])
        );

        $response->assertRedirect(route('store.admin.vouchers.index', ['store_slug' => $store->slug]));
    }
}
