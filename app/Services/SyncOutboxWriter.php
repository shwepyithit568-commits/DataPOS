<?php

namespace App\Services;

use App\Models\Store;
use App\Models\SyncOutboxRecord;
use App\POS\Models\PosSale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * The single writer for the replication outbox.
 *
 * A shop that sells offline keeps its own database as the truth; the outbox is
 * the queue that carries those records to a central installation later. Two
 * rules shape this class:
 *
 *  1. Nothing here may ever break a sale. PosSaleService calls it while posting,
 *     so a bookkeeping failure is logged and swallowed — the cashier's receipt
 *     is more important than the queue row.
 *  2. It must not depend on PosSaleService (which depends on it), or the
 *     container would build a second PosSaleService mid-post.
 */
class SyncOutboxWriter
{
    public const POS_SALE = 'pos_sale';

    public const CUSTOMER_DEBT = 'customer_debt';

    public function enabled(): bool
    {
        return (bool) config('sync.enabled', false);
    }

    /**
     * This installation replicates its sales to a central one.
     */
    public function isTerminal(): bool
    {
        return $this->enabled() && (string) config('sync.role') === 'terminal';
    }

    /**
     * Config keys that must be filled before a push can leave the building.
     *
     * @return list<string>
     */
    public function configProblems(): array
    {
        if (! $this->enabled()) {
            return ['DATAPOS_SYNC_ENABLED'];
        }

        if (! $this->isTerminal()) {
            return ['DATAPOS_SYNC_ROLE'];
        }

        $problems = [];

        foreach (['central_url', 'store_slug', 'api_key'] as $key) {
            if ((string) config("sync.{$key}") === '') {
                $problems[] = 'DATAPOS_SYNC_' . strtoupper($key);
            }
        }

        return $problems;
    }

    public function ready(): bool
    {
        return $this->configProblems() === [];
    }

    /**
     * A replication key that is unique across every terminal of a store, so the
     * central can recognise a retry instead of recording a second sale.
     */
    public function newClientTransactionId(Store $store): string
    {
        return 'sale-' . $store->id . '-' . Str::ulid()->toBase32();
    }

    /**
     * Queue a record for the central installation.
     */
    public function enqueue(
        Store $store,
        string $recordType,
        string $clientTxId,
        array $payload,
        ?Carbon $offlineCreatedAt = null,
        ?string $deviceId = null,
        ?int $branchId = null
    ): SyncOutboxRecord {
        return SyncOutboxRecord::updateOrCreate(
            [
                'store_id'              => $store->id,
                'client_transaction_id' => $clientTxId,
            ],
            [
                'branch_id'          => $branchId,
                'device_id'          => $deviceId,
                'record_type'        => $recordType,
                'payload'            => $payload,
                'status'             => 'pending',
                'error_message'      => null,
                'created_offline_at' => $offlineCreatedAt ?? now(),
            ]
        );
    }

    /**
     * Queue a posted sale for replication.
     *
     * The payload carries the prices the counter actually charged and the
     * discount that was actually given, because the central must record the
     * shop's money — not re-price the bill from its own catalog.
     */
    public function recordPostedSale(Store $store, PosSale $sale): ?SyncOutboxRecord
    {
        if (! $this->isTerminal()) {
            return null;
        }

        try {
            $sale->loadMissing(['items', 'payments', 'customer']);

            $payload = [
                'terminal_sale_id' => $sale->id,
                'receipt_number'   => $sale->receipt_number,
                'cashier_id'       => $sale->cashier_id,
                'cashier_shift_id' => $sale->cashier_shift_id,
                'customer_id'      => $sale->customer_id,
                // The exact discount given at the counter (manual + coupon +
                // redeemed points). Without this the central would apply its own
                // session discount — usually none — and the two books diverge.
                'discount'         => (string) $sale->discount,
                // Carried for verification only: the central re-derives the
                // total and we compare, so a silent divergence is impossible.
                'expected_total'   => (string) $sale->total,
                'expected_subtotal' => (string) $sale->subtotal,
                'coupon_code'      => $sale->coupon_code,
                'lines'            => $sale->items->map(fn ($item) => [
                    'product_id'         => (int) $item->product_id,
                    'product_variant_id' => $item->product_variant_id ? (int) $item->product_variant_id : null,
                    'quantity'           => (string) $item->quantity,
                    'unit_price'         => (string) $item->unit_price,
                ])->values()->all(),
                'payments'         => $sale->payments->map(fn ($payment) => [
                    'method'    => (string) $payment->method,
                    'amount'    => (string) $payment->amount,
                    'reference' => $payment->reference,
                ])->values()->all(),
                'posted_at'        => optional($sale->posted_at)->toIso8601String(),
            ];

            $clientTxId = $sale->client_transaction_id ?: $this->newClientTransactionId($store);

            // Stamp the sale so the queue row, the local record and the central's
            // copy all share one key (that key IS the idempotency guarantee).
            if (! $sale->client_transaction_id) {
                $sale->forceFill(['client_transaction_id' => $clientTxId])->save();
            }

            return $this->enqueue(
                store: $store,
                recordType: self::POS_SALE,
                clientTxId: $clientTxId,
                payload: $payload,
                offlineCreatedAt: $sale->posted_at ?? now(),
                deviceId: $this->deviceId(),
                branchId: $sale->branch_id,
            );
        } catch (\Throwable $e) {
            // A queue failure must never cost the shop its receipt.
            Log::warning('Sync outbox write failed for sale ' . $sale->id . ': ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Stable name for this terminal, recorded on every queue row so a shop with
     * several counters can tell where a record came from.
     */
    public function deviceId(): string
    {
        $configured = (string) env('DATAPOS_SYNC_DEVICE_ID', '');

        if ($configured !== '') {
            return Str::limit($configured, 64, '');
        }

        return Str::limit(gethostname() ?: 'terminal', 64, '');
    }
}
