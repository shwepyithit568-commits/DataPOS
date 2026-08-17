<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ImportHistory;
use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DebtOpeningImportTest extends TestCase
{
    use RefreshDatabase;

    private function makeManagerAndStore(): array
    {
        $store = Store::create([
            'name' => 'Test Store',
            'slug' => 'test-store',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Manager',
            'phone' => '09123456789',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $user->stores()->attach($store->id, [
            'role' => 'store_manager',
            'status' => 'active',
        ]);

        return [$store, $user];
    }

    private function makeCustomer(Store $store, string $name, string $phone): User
    {
        $customer = User::create([
            'name' => $name,
            'phone' => $phone,
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $customer->stores()->attach($store->id, [
            'role' => 'retail_customer',
            'status' => 'active',
        ]);

        return $customer;
    }

    private function makeCsv(string $content, string $filename = 'debt.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'debt_test_') . '.csv';
        file_put_contents($path, $content);

        return new UploadedFile($path, $filename, 'text/csv', null, true);
    }

    private function preview(Store $store, User $manager, UploadedFile $file): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/pilot-import/debt", [
                'file' => $file,
            ]);
    }

    private function previewAndConfirm(Store $store, User $manager, UploadedFile $file): \Illuminate\Testing\TestResponse
    {
        $previewResponse = $this->preview($store, $manager, $file);
        $previewResponse->assertRedirect();
        $previewResponse->assertSessionHas('import_preview');

        $preview = $previewResponse->getSession()->get('import_preview');

        return $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/pilot-import/debt/confirm", [
                'token' => $preview['token'],
            ]);
    }

    // ─────────────────────────── Hub page ───────────────────────────

    public function test_debt_tab_renders(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/pilot-import/debt")
            ->assertOk()
            ->assertSee('Debt', false);
    }

    public function test_debt_import_requires_store_access(): void
    {
        Storage::fake('local');
        $store = Store::create(['name' => 'Other Store', 'slug' => 'other-store', 'is_active' => true]);
        $outsider = User::create([
            'name' => 'Outsider',
            'phone' => '09999999999',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        $this->actingAs($outsider)
            ->get("/store/{$store->slug}/admin/pilot-import/debt")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->post("/store/{$store->slug}/admin/pilot-import/debt", ['file' => $this->makeCsv("phone,amount\n0991111111,50000")])
            ->assertForbidden();
    }

    public function test_unknown_tab_still_404s(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/pilot-import/inventory")
            ->assertNotFound();
    }

    // ─────────────────────────── Preview ───────────────────────────

    public function test_preview_is_dry_run_and_resolves_customers(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $maSu = $this->makeCustomer($store, 'Ma Su', '09123456780');
        $koKo = $this->makeCustomer($store, 'Ko Ko', '09771234567');

        // Ma Su already owes 20,000 from an earlier credit sale.
        CustomerLedgerEntry::create([
            'store_id' => $store->id,
            'customer_id' => $maSu->id,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => '20000.00',
            'source_type' => 'pos_sale',
            'source_id' => 1,
            'notes' => 'Debt from sale #1',
            'occurred_at' => now(),
            'created_by' => $manager->id,
        ]);

        $csv = "phone,amount,notes\n"
            . "09123456780,150000,Old ledger\n"
            . "09 771 234 567,25000,\n"
            . "09222222222,50000,\n";

        $response = $this->preview($store, $manager, $this->makeCsv($csv));
        $response->assertRedirect();
        $response->assertSessionHas('import_preview');

        $preview = $response->getSession()->get('import_preview');
        $this->assertSame(3, $preview['total']);
        $this->assertSame(2, $preview['found']);
        $this->assertSame(1, $preview['not_found']);
        $this->assertSame(1, $preview['failed']);
        $this->assertSame('175000.00', $preview['total_amount']);

        // Ma Su's row carries her current balance for the before/after view.
        $rows = collect($preview['preview_rows'])->keyBy('phone');
        $this->assertSame('Ma Su', $rows['9123456780']['name']);
        $this->assertSame('150000', $rows['9123456780']['amount']);
        $this->assertSame('20000.00', $rows['9123456780']['balance']);
        $this->assertSame('post', $rows['9123456780']['action']);
        $this->assertSame('not_found', $rows['9222222222']['action']);

        // Dry run — nothing NEW written (the pre-seeded sale-debt entry stays).
        $this->assertSame(1, CustomerLedgerEntry::count());
        $this->assertSame(0, ImportHistory::count());
    }

    // ─────────────────────────── Confirm ───────────────────────────

    public function test_confirm_posts_opening_balance_entries(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $maSu = $this->makeCustomer($store, 'Ma Su', '09123456780');
        $koKo = $this->makeCustomer($store, 'Ko Ko', '09771234567');

        $csv = "phone,amount,notes\n"
            . "09123456780,150000,Old ledger\n"
            . "09771234567,25000,\n";

        $response = $this->previewAndConfirm($store, $manager, $this->makeCsv($csv));
        $response->assertRedirect();
        $response->assertSessionHas('import_result');

        $result = $response->getSession()->get('import_result');
        $this->assertSame(2, $result['posted']);
        $this->assertSame('175000.00', $result['total_amount']);

        $entries = CustomerLedgerEntry::where('store_id', $store->id)->orderBy('customer_id')->get();
        $this->assertSame(2, $entries->count());

        $byCustomer = $entries->keyBy('customer_id');
        $this->assertSame(CustomerLedgerEntry::TYPE_OPENING_BALANCE, $byCustomer[$maSu->id]->type);
        $this->assertSame('150000.00', $byCustomer[$maSu->id]->amount);
        $this->assertSame('manual', $byCustomer[$maSu->id]->source_type);
        $this->assertSame('Old ledger', $byCustomer[$maSu->id]->notes);
        $this->assertSame($manager->id, $byCustomer[$maSu->id]->created_by);

        $this->assertSame(CustomerLedgerEntry::TYPE_OPENING_BALANCE, $byCustomer[$koKo->id]->type);
        $this->assertSame('25000.00', $byCustomer[$koKo->id]->amount);

        // History + audit.
        $this->assertSame(1, ImportHistory::where('type', 'debt')->count());
        $this->assertSame(2, ImportHistory::first()->success_rows);
        $this->assertTrue(AuditLog::where('action', 'debt_opening_imported')->exists());
    }

    public function test_duplicate_phone_in_file_is_skipped(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $maSu = $this->makeCustomer($store, 'Ma Su', '09123456780');

        $csv = "phone,amount\n"
            . "09123456780,150000\n"
            . "09123456780,50000\n";

        $response = $this->previewAndConfirm($store, $manager, $this->makeCsv($csv));
        $result = $response->getSession()->get('import_result');

        $this->assertSame(1, $result['posted']);
        $this->assertSame(1, $result['failed']);

        // Only ONE entry — the duplicate was never double-posted.
        $this->assertSame(1, CustomerLedgerEntry::where('customer_id', $maSu->id)->count());
        $this->assertSame('150000.00', CustomerLedgerEntry::where('customer_id', $maSu->id)->first()->amount);
    }

    public function test_invalid_rows_fail_without_posting(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $maSu = $this->makeCustomer($store, 'Ma Su', '09123456780');

        $csv = "phone,amount\n"
            . "09123456780,150000\n"
            . "09123456780,\n"          // empty amount
            . "not-a-phone,50000\n"     // invalid phone
            . "09123456780,-100\n";     // negative amount

        $response = $this->previewAndConfirm($store, $manager, $this->makeCsv($csv));
        $result = $response->getSession()->get('import_result');

        $this->assertSame(1, $result['posted']);
        $this->assertSame(3, $result['failed']);
        $this->assertSame(1, CustomerLedgerEntry::count());
    }

    public function test_cross_store_customer_is_not_found_and_not_posted(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();
        $storeB = Store::create(['name' => 'Store B', 'slug' => 'store-b', 'is_active' => true]);
        $this->makeCustomer($storeB, 'Su Su', '09444555666');

        $csv = "phone,amount\n09444555666,100000\n";

        $response = $this->previewAndConfirm($store, $manager, $this->makeCsv($csv));
        $result = $response->getSession()->get('import_result');

        $this->assertSame(0, $result['posted']);
        $this->assertSame(1, $result['not_found']);

        // No receivable was created in store A for store B's customer.
        $this->assertSame(0, CustomerLedgerEntry::count());
    }

    public function test_confirm_with_expired_token_fails(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $this->actingAs($manager)
            ->post("/store/{$store->slug}/admin/pilot-import/debt/confirm", ['token' => 'bogus-token'])
            ->assertRedirect()
            ->assertSessionHasErrors('file');
    }

    public function test_debt_template_downloads(): void
    {
        Storage::fake('local');
        [$store, $manager] = $this->makeManagerAndStore();

        $response = $this->actingAs($manager)
            ->get("/store/{$store->slug}/admin/pilot-import/debt/template");

        $response->assertOk();
        $this->assertStringContainsString('phone,amount,notes', $response->streamedContent());
    }
}
