<?php

namespace Tests\Feature\Admin;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\FinancialTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBankTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'test-store',
            'name' => 'Test Store POS',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->staff = User::factory()->create(['role' => 'staff']);
        $this->staff->stores()->attach($this->store->id, ['role' => 'staff']);
    }

    public function test_transactions_index_page_renders_with_accounts_and_kpis(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.transactions.index', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertSee('Cash in Hand');
        $response->assertSee('KBZPay');
        $response->assertSee(__('messages.transactions_total_liquidity'));
    }

    public function test_deposit_increases_account_balance_and_creates_transaction(): void
    {
        $service = app(FinancialTransactionService::class);
        $service->ensureDefaultAccounts($this->store);

        $cashAccount = FinancialAccount::where('store_id', $this->store->id)
            ->where('code', 'cash_in_hand')
            ->first();

        $this->assertNotNull($cashAccount);
        $this->assertEquals(0.00, (float) $cashAccount->current_balance);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.deposit', ['store_slug' => $this->store->slug]), [
                'to_account_id' => $cashAccount->id,
                'amount' => 500000,
                'category' => 'capital_injection',
                'payer_or_payee' => 'Shop Owner',
                'reference_no' => 'DEP-001',
                'notes' => 'Initial capital injection',
            ]);

        $response->assertRedirect(route('store.admin.transactions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $cashAccount->refresh();
        $this->assertEquals(500000.00, (float) $cashAccount->current_balance);

        $this->assertDatabaseHas('financial_transactions', [
            'store_id' => $this->store->id,
            'to_account_id' => $cashAccount->id,
            'type' => 'deposit',
            'amount' => 500000.00,
            'payer_or_payee' => 'Shop Owner',
        ]);
    }

    public function test_withdrawal_decreases_account_balance(): void
    {
        $service = app(FinancialTransactionService::class);
        $service->ensureDefaultAccounts($this->store);

        $cashAccount = FinancialAccount::where('store_id', $this->store->id)
            ->where('code', 'cash_in_hand')
            ->first();

        $cashAccount->update(['current_balance' => 300000.00]);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.withdraw', ['store_slug' => $this->store->slug]), [
                'from_account_id' => $cashAccount->id,
                'amount' => 100000,
                'category' => 'owner_drawing',
                'payer_or_payee' => 'Owner',
                'notes' => 'Personal drawing',
            ]);

        $response->assertRedirect(route('store.admin.transactions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $cashAccount->refresh();
        $this->assertEquals(200000.00, (float) $cashAccount->current_balance);

        $this->assertDatabaseHas('financial_transactions', [
            'store_id' => $this->store->id,
            'from_account_id' => $cashAccount->id,
            'type' => 'withdrawal',
            'amount' => 100000.00,
        ]);
    }

    public function test_transfer_between_accounts_with_fee(): void
    {
        $service = app(FinancialTransactionService::class);
        $service->ensureDefaultAccounts($this->store);

        $cashAccount = FinancialAccount::where('store_id', $this->store->id)->where('code', 'cash_in_hand')->first();
        $kpayAccount = FinancialAccount::where('store_id', $this->store->id)->where('code', 'kpay_wallet')->first();

        $cashAccount->update(['current_balance' => 600000.00]);
        $kpayAccount->update(['current_balance' => 50000.00]);

        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.transfer', ['store_slug' => $this->store->slug]), [
                'from_account_id' => $cashAccount->id,
                'to_account_id' => $kpayAccount->id,
                'amount' => 200000,
                'fee' => 500,
                'reference_no' => 'TRF-KPay-01',
                'notes' => 'Deposit cash into KPay wallet',
            ]);

        $response->assertRedirect(route('store.admin.transactions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $cashAccount->refresh();
        $kpayAccount->refresh();

        // Cash was deducted amount + fee = 200500 -> 600000 - 200500 = 399500
        $this->assertEquals(399500.00, (float) $cashAccount->current_balance);
        // KPay was incremented amount = 200000 -> 50000 + 200000 = 250000
        $this->assertEquals(250000.00, (float) $kpayAccount->current_balance);

        $this->assertDatabaseHas('financial_transactions', [
            'store_id' => $this->store->id,
            'from_account_id' => $cashAccount->id,
            'to_account_id' => $kpayAccount->id,
            'type' => 'transfer',
            'amount' => 200000.00,
            'fee' => 500.00,
        ]);
    }

    public function test_create_new_financial_account(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.account.store', ['store_slug' => $this->store->slug]), [
                'name' => 'Yoma Bank (Business)',
                'account_type' => 'bank_account',
                'account_number' => '00987654321',
                'account_holder' => 'U Hla Maung',
                'opening_balance' => 1500000,
                'notes' => 'New business account',
            ]);

        $response->assertRedirect(route('store.admin.transactions.index', ['store_slug' => $this->store->slug]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('financial_accounts', [
            'store_id' => $this->store->id,
            'name' => 'Yoma Bank (Business)',
            'current_balance' => 1500000.00,
        ]);
    }

    public function test_export_transactions_csv(): void
    {
        $service = app(FinancialTransactionService::class);
        $service->ensureDefaultAccounts($this->store);

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.transactions.export', ['store_slug' => $this->store->slug]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_printable_voucher_view(): void
    {
        $service = app(FinancialTransactionService::class);
        $service->ensureDefaultAccounts($this->store);

        $cashAccount = FinancialAccount::where('store_id', $this->store->id)->where('code', 'cash_in_hand')->first();

        $transaction = $service->recordDeposit($this->store, [
            'to_account_id' => $cashAccount->id,
            'amount' => 150000,
            'category' => 'capital_injection',
            'payer_or_payee' => 'Ko Aung',
        ], $this->manager);

        $response = $this->actingAs($this->manager)
            ->get(route('store.admin.transactions.voucher', [
                'store_slug' => $this->store->slug,
                'transaction' => $transaction->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee($transaction->transaction_number);
        $response->assertSee('150,000.00');
    }
}
