<?php

namespace Tests\Feature\Admin;

use App\Models\FinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Store;
use App\Models\User;
use App\POS\Services\FinancialTransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: cash & bank accounts used to move money as PHP floats and never
 * checked the balance, so a withdrawal could overdraw an account and binary
 * rounding could reach the stored balance.
 */
class FinancialAccountGuardTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $manager;
    protected FinancialTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'slug' => 'finance-store',
            'name' => 'Finance Store',
            'is_active' => true,
        ]);

        $this->manager = User::factory()->create(['role' => 'store_manager']);
        $this->manager->stores()->attach($this->store->id, ['role' => 'store_manager']);

        $this->service = app(FinancialTransactionService::class);
        $this->service->ensureDefaultAccounts($this->store);
    }

    private function account(string $code = 'cash_in_hand'): FinancialAccount
    {
        return FinancialAccount::where('store_id', $this->store->id)->where('code', $code)->firstOrFail();
    }

    public function test_deposit_stores_an_exact_decimal_balance(): void
    {
        $this->service->recordDeposit($this->store, [
            'to_account_id' => $this->account()->id,
            'amount'        => '1234.57',
        ], $this->manager);

        $this->assertSame('1234.57', (string) $this->account()->fresh()->current_balance);
    }

    public function test_repeated_cent_deposits_do_not_drift(): void
    {
        $accountId = $this->account()->id;

        // 0.1 + 0.2 style drift would show up here as 0.30000000000000004.
        for ($i = 0; $i < 10; $i++) {
            $this->service->recordDeposit($this->store, [
                'to_account_id' => $accountId,
                'amount'        => '0.10',
            ], $this->manager);
        }

        $this->assertSame('1.00', (string) $this->account()->fresh()->current_balance);
    }

    public function test_withdrawal_beyond_the_balance_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordWithdrawal($this->store, [
            'from_account_id' => $this->account()->id,
            'amount'          => '50.00',
        ], $this->manager);
    }

    public function test_a_rejected_withdrawal_writes_nothing(): void
    {
        try {
            $this->service->recordWithdrawal($this->store, [
                'from_account_id' => $this->account()->id,
                'amount'          => '50.00',
            ], $this->manager);
        } catch (\InvalidArgumentException) {
            // expected
        }

        $this->assertSame('0.00', (string) $this->account()->fresh()->current_balance);
        $this->assertSame(0, FinancialTransaction::where('store_id', $this->store->id)->count());
    }

    public function test_withdrawal_may_empty_an_account_but_not_overdraw_it(): void
    {
        $accountId = $this->account()->id;

        $this->service->recordDeposit($this->store, [
            'to_account_id' => $accountId,
            'amount'        => '500.00',
        ], $this->manager);

        $this->service->recordWithdrawal($this->store, [
            'from_account_id' => $accountId,
            'amount'          => '500.00',
        ], $this->manager);

        $this->assertSame('0.00', (string) $this->account()->fresh()->current_balance);
    }

    public function test_transfer_guard_includes_the_fee(): void
    {
        $cash = $this->account('cash_in_hand');
        $bank = $this->account('kbz_bank');

        $this->service->recordDeposit($this->store, [
            'to_account_id' => $cash->id,
            'amount'        => '100.00',
        ], $this->manager);

        // 100 out + 1 fee is more than the 100 available.
        $this->expectException(\InvalidArgumentException::class);

        $this->service->recordTransfer($this->store, [
            'from_account_id' => $cash->id,
            'to_account_id'   => $bank->id,
            'amount'          => '100.00',
            'fee'             => '1.00',
        ], $this->manager);
    }

    public function test_transfer_moves_the_amount_and_only_the_fee_extra(): void
    {
        $cash = $this->account('cash_in_hand');
        $bank = $this->account('kbz_bank');

        $this->service->recordDeposit($this->store, [
            'to_account_id' => $cash->id,
            'amount'        => '100.00',
        ], $this->manager);

        $this->service->recordTransfer($this->store, [
            'from_account_id' => $cash->id,
            'to_account_id'   => $bank->id,
            'amount'          => '99.00',
            'fee'             => '1.00',
        ], $this->manager);

        $this->assertSame('0.00', (string) $cash->fresh()->current_balance);
        $this->assertSame('99.00', (string) $bank->fresh()->current_balance);
    }

    public function test_account_creation_and_its_opening_entry_land_together(): void
    {
        $account = $this->service->createAccount($this->store, [
            'name'            => 'Wave Money',
            'account_type'    => 'mobile_wallet',
            'opening_balance' => '250.25',
        ], $this->manager);

        $this->assertSame('250.25', (string) $account->fresh()->current_balance);
        $this->assertSame(
            1,
            FinancialTransaction::where('store_id', $this->store->id)
                ->where('to_account_id', $account->id)
                ->where('category', 'opening_balance')
                ->count()
        );
    }

    public function test_scientific_notation_is_rejected_instead_of_erroring(): void
    {
        $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.deposit', ['store_slug' => $this->store->slug]), [
                'to_account_id' => $this->account()->id,
                'amount'        => '1e3',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame('0.00', (string) $this->account()->fresh()->current_balance);
    }

    public function test_withdrawing_from_another_stores_account_is_rejected(): void
    {
        $otherStore = Store::create(['slug' => 'other-finance-store', 'name' => 'Other', 'is_active' => true]);
        $this->service->ensureDefaultAccounts($otherStore);
        $foreign = FinancialAccount::where('store_id', $otherStore->id)->where('code', 'cash_in_hand')->firstOrFail();

        $this->actingAs($this->manager)
            ->post(route('store.admin.transactions.withdraw', ['store_slug' => $this->store->slug]), [
                'from_account_id' => $foreign->id,
                'amount'          => '10.00',
            ])
            ->assertSessionHasErrors('from_account_id');
    }
}
