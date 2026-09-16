<?php

namespace App\POS\Services;

use App\Models\Store;
use App\POS\Models\DocumentSequence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DocumentSequenceService
{
    /**
     * Default configurations per document type.
     *
     * @var array<string, array{prefix: string, pad_length: int, period_format: string}>
     */
    protected array $typeConfigs = [
        'sale' => [
            'prefix' => 'RCP-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'pos_sale' => [
            'prefix' => 'RCP-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'invoice' => [
            'prefix' => 'INV-{Y}-',
            'pad_length' => 5,
            'period_format' => 'Y',
        ],
        'return' => [
            'prefix' => 'RET-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'pos_return' => [
            'prefix' => 'RET-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'purchase_order' => [
            'prefix' => 'PO-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'goods_receipt' => [
            'prefix' => 'GRN-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'purchase_return' => [
            'prefix' => 'PRET-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'service_job' => [
            'prefix' => 'SVC-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'expense' => [
            'prefix' => 'EXP-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'buy_back' => [
            'prefix' => 'BB-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'stock_count' => [
            'prefix' => 'SC-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'opening_stock' => [
            'prefix' => 'OSR-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'adjustment' => [
            'prefix' => 'ADJ-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'stock_adjustment' => [
            'prefix' => 'ADJ-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'transfer' => [
            'prefix' => 'TRF-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
        'stock_transfer' => [
            'prefix' => 'TRF-{Ymd}-',
            'pad_length' => 4,
            'period_format' => 'Ymd',
        ],
    ];

    /**
     * Generate the next atomic, collision-free document number for a store.
     *
     * Guaranteed safe across concurrent web workers and background jobs.
     */
    public function nextNumber(
        Store $store,
        string $documentType,
        string|\DateTimeInterface|null $customPrefix = null,
        int $padLength = 4,
        ?\DateTimeInterface $date = null
    ): string {
        $cfg = $this->typeConfigs[$documentType] ?? [
            'prefix' => strtoupper($documentType) . '-{Ymd}-',
            'pad_length' => $padLength,
            'period_format' => 'Ymd',
        ];

        $effectiveDate = now();
        $prefix = null;

        if ($customPrefix instanceof \DateTimeInterface) {
            $effectiveDate = Carbon::parse($customPrefix);
        } elseif (is_string($customPrefix) && $customPrefix !== '') {
            $prefix = $customPrefix;
        }

        if ($date instanceof \DateTimeInterface) {
            $effectiveDate = Carbon::parse($date);
        }

        $periodKey = $effectiveDate->format($cfg['period_format']);

        // Resolve prefix template
        $rawPrefix = $prefix ?: $cfg['prefix'];
        $renderedPrefix = str_replace(
            ['{Ymd}', '{Ym}', '{Y}'],
            [$effectiveDate->format('Ymd'), $effectiveDate->format('Ym'), $effectiveDate->format('Y')],
            $rawPrefix
        );

        $digits = $prefix ? $padLength : $cfg['pad_length'];

        return DB::transaction(function () use ($store, $documentType, $periodKey, $renderedPrefix, $digits) {
            /** @var DocumentSequence $sequence */
            $sequence = DocumentSequence::query()
                ->where('store_id', $store->id)
                ->where('document_type', $documentType)
                ->where('period_key', $periodKey)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                // Determine starting sequence by checking any existing records for seamless migration
                $existingMax = $this->determineExistingMax($store, $documentType, $renderedPrefix);

                $sequence = DocumentSequence::create([
                    'store_id' => $store->id,
                    'document_type' => $documentType,
                    'period_key' => $periodKey,
                    'last_number' => $existingMax,
                ]);
            }

            $sequence->increment('last_number');
            $nextNum = $sequence->last_number;

            return $renderedPrefix . str_pad((string) $nextNum, $digits, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Where each document type keeps its number, so a sequence started fresh
     * (new day, new period) can resume after the rows that already exist.
     *
     * @var array<string, array{0: string, 1: string}>
     */
    protected array $numberColumns = [
        'sale'             => ['pos_sales', 'receipt_number'],
        'pos_sale'         => ['pos_sales', 'receipt_number'],
        'return'           => ['pos_returns', 'refund_number'],
        'pos_return'       => ['pos_returns', 'refund_number'],
        'goods_receipt'    => ['goods_receipts', 'receipt_number'],
        'purchase_order'   => ['purchase_orders', 'po_number'],
        'purchase_return'  => ['purchase_returns', 'return_number'],
        'adjustment'       => ['inventory_adjustments', 'adjustment_number'],
        'stock_adjustment' => ['inventory_adjustments', 'adjustment_number'],
        'service_job'      => ['service_jobs', 'job_number'],
        'expense'          => ['expenses', 'expense_number'],
        'buy_back'         => ['buy_backs', 'buyback_number'],
        'stock_transfer'   => ['stock_transfers', 'transfer_number'],
        'transfer'         => ['stock_transfers', 'transfer_number'],
        'opening_stock'    => ['opening_stock_requests', 'request_number'],
        'stock_count'      => ['stock_counts', 'session_number'],
    ];

    /**
     * Inspect existing rows so a newly created sequence never re-issues a number
     * that is already in use. Reads the highest numeric suffix (not a row count,
     * which drifts as soon as a document is deleted).
     */
    protected function determineExistingMax(Store $store, string $documentType, string $prefix): int
    {
        [$table, $column] = $this->numberColumns[$documentType] ?? [null, null];

        if ($table === null || $column === null) {
            return 0;
        }

        try {
            $latest = DB::table($table)
                ->where('store_id', $store->id)
                ->where($column, 'like', $prefix . '%')
                ->orderByDesc($column)
                ->value($column);

            if ($latest === null) {
                return 0;
            }

            return preg_match('/-(\d+)$/', (string) $latest, $matches) === 1
                ? (int) $matches[1]
                : 0;
        } catch (\Throwable) {
            return 0;
        }
    }
}
