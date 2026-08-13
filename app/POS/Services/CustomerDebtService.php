<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Exceptions\InventoryException;
use App\POS\Models\CustomerLedgerEntry;
use Illuminate\Support\Facades\DB;

/**
 * Customer receivable ledger (SoT §17 — Debt and Finance).
 *
 * Rules enforced here:
 * - Debt is recorded as a NEW entry referencing its source sale — never a
 *   direct edit of a stored balance.
 * - Collections reduce the balance with a new `collection` entry; the balance
 *   itself is always derived as SUM(amount).
 * - Posted entries are immutable — corrections are `reversal` entries.
 * - client_transaction_id makes retries idempotent (unique per store).
 * - Money is bcmath decimal (MMK, §2.6) — no floats.
 */
class CustomerDebtService
{
    /* ------------------------------------------------------------------ */
    /*  Writing entries                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Record a sale's deferred (credit) payment as a debt receivable.
     * Called inside the sale-posting transaction so sale + receivable stay
     * atomic. Idempotent via $clientTransactionId.
     */
    public function recordSaleDebt(
        Store $store,
        int $customerId,
        int $saleId,
        string $amount,
        User $actor,
        ?string $clientTransactionId = null,
        ?int $branchId = null,
    ): CustomerLedgerEntry {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Debt amount must be positive.');
        }

        return $this->createEntry($store, [
            'customer_id' => $customerId,
            'branch_id' => $branchId,
            'type' => CustomerLedgerEntry::TYPE_SALE_DEBT,
            'amount' => $amount,
            'source_type' => 'pos_sale',
            'source_id' => $saleId,
            'notes' => "Debt from sale #{$saleId}",
            'created_by' => $actor->id,
            'client_transaction_id' => $clientTransactionId,
        ]);
    }

    /**
     * Collect part (or all) of a customer's outstanding debt. Always a NEW
     * transaction — the balance is never edited directly (SoT §17).
     * Idempotent via $clientTransactionId.
     */
    public function collect(
        Store $store,
        int $customerId,
        string $amount,
        User $actor,
        ?string $notes = null,
        ?string $clientTransactionId = null,
    ): CustomerLedgerEntry {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Collection amount must be positive.');
        }

        $outstanding = $this->balanceFor($store->id, $customerId);
        if (bccomp($amount, $outstanding, 2) > 0) {
            throw new InventoryException(
                'Cannot collect more than the outstanding balance (Ks ' . rtrim(rtrim($outstanding, '0'), '.') . ').'
            );
        }

        return $this->createEntry($store, [
            'customer_id' => $customerId,
            'type' => CustomerLedgerEntry::TYPE_COLLECTION,
            'amount' => '-' . $amount,
            'source_type' => 'manual',
            'notes' => $notes ?: 'Debt collection',
            'created_by' => $actor->id,
            'client_transaction_id' => $clientTransactionId,
        ]);
    }

    /**
     * Reduce a customer's receivable when a credit-sold item is refunded.
     * A NEW `refund` entry (negative amount) referencing the return — the
     * balance is derived as SUM(amount), never edited directly (SoT §17).
     */
    public function recordSaleRefund(
        Store $store,
        int $customerId,
        int $returnId,
        string $amount,
        User $actor,
        ?string $clientTransactionId = null,
    ): CustomerLedgerEntry {
        if (bccomp($amount, '0', 2) <= 0) {
            throw new InventoryException('Refund amount must be positive.');
        }

        return $this->createEntry($store, [
            'customer_id' => $customerId,
            'type' => 'refund',
            'amount' => '-' . $amount,
            'source_type' => 'pos_return',
            'source_id' => $returnId,
            'notes' => "Refund from return #{$returnId}",
            'created_by' => $actor->id,
            'client_transaction_id' => $clientTransactionId,
        ]);
    }

    /**
     * Reverse a posted ledger entry (SoT §17 — corrections use reversals).
     * Creates an opposite-sign `reversal` entry referencing the original.
     */
    public function reverse(
        Store $store,
        CustomerLedgerEntry $entry,
        User $actor,
        ?string $reason = null,
    ): CustomerLedgerEntry {
        if ((int) $entry->store_id !== (int) $store->id) {
            throw new InventoryException('Cannot reverse a ledger entry from another store.');
        }

        return $this->createEntry($store, [
            'customer_id' => $entry->customer_id,
            'branch_id' => $entry->branch_id,
            'type' => CustomerLedgerEntry::TYPE_REVERSAL,
            'amount' => bccomp((string) $entry->amount, '0', 2) > 0 ? '-' . $entry->amount : ltrim($entry->amount, '-'),
            'source_type' => 'customer_ledger_entry',
            'source_id' => $entry->id,
            'notes' => $reason ?: "Reversal of ledger entry #{$entry->id}",
            'created_by' => $actor->id,
            'client_transaction_id' => null,
        ]);
    }

    /**
     * Create an entry if the client transaction hasn't been posted yet
     * (idempotency — offline retries must not double-post).
     */
    private function createEntry(Store $store, array $data): CustomerLedgerEntry
    {
        return DB::transaction(function () use ($store, $data) {
            $clientTxn = $data['client_transaction_id'] ?? null;

            if ($clientTxn !== null) {
                $existing = CustomerLedgerEntry::query()
                    ->where('store_id', $store->id)
                    ->where('client_transaction_id', $clientTxn)
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $data['store_id'] = $store->id;
            $data['occurred_at'] = now();

            return CustomerLedgerEntry::create($data);
        });
    }

    /* ------------------------------------------------------------------ */
    /*  Reading                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Outstanding debt for one customer in a store = SUM(amount).
     */
    public function balanceFor(int $storeId, int $customerId): string
    {
        $total = CustomerLedgerEntry::query()
            ->where('store_id', $storeId)
            ->where('customer_id', $customerId)
            ->sum('amount');

        return number_format((float) $total, 2, '.', '');
    }

    /**
     * Customers of the store with a non-zero outstanding balance, newest
     * activity first.
     *
     * @return array<int, array{customer_id:int, name:string, phone:?string, balance:string, last_activity:?string}>
     */
    public function outstandingCustomers(Store $store, int $limit = 50): array
    {
        $rows = DB::table('customer_ledger_entries')
            ->join('users', 'users.id', '=', 'customer_ledger_entries.customer_id')
            ->where('customer_ledger_entries.store_id', $store->id)
            ->groupBy('customer_ledger_entries.customer_id', 'users.name', 'users.phone')
            ->selectRaw(
                'customer_ledger_entries.customer_id, users.name, users.phone, '
                . 'SUM(customer_ledger_entries.amount) AS balance, '
                . 'MAX(customer_ledger_entries.occurred_at) AS last_activity'
            )
            ->havingRaw('ABS(SUM(customer_ledger_entries.amount)) > 0')
            ->orderByDesc('last_activity')
            ->limit($limit)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $balance = number_format((float) $row->balance, 2, '.', '');
            if (bccomp($balance, '0', 2) === 0) {
                continue;
            }

            $out[] = [
                'customer_id' => (int) $row->customer_id,
                'name' => $row->name,
                'phone' => $row->phone,
                'balance' => $balance,
                'last_activity' => $row->last_activity,
            ];
        }

        return $out;
    }

    /**
     * Recent ledger entries for a customer (debt history).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CustomerLedgerEntry>
     */
    public function history(Store $store, int $customerId, int $limit = 20)
    {
        return CustomerLedgerEntry::query()
            ->with('actor')
            ->where('store_id', $store->id)
            ->where('customer_id', $customerId)
            ->latest('occurred_at')
            ->limit($limit)
            ->get();
    }
}
