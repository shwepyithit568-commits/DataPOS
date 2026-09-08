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
            'prefix' => 'SRV-{Ymd}-',
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
     * Inspect existing tables to prevent collision with previously created documents.
     */
    protected function determineExistingMax(Store $store, string $documentType, string $prefix): int
    {
        try {
            return match ($documentType) {
                'sale' => (int) DB::table('pos_sales')
                    ->where('store_id', $store->id)
                    ->where('receipt_number', 'like', $prefix . '%')
                    ->count(),
                'return' => (int) DB::table('pos_returns')
                    ->where('store_id', $store->id)
                    ->where('refund_number', 'like', $prefix . '%')
                    ->count(),
                'goods_receipt' => (int) DB::table('goods_receipts')
                    ->where('store_id', $store->id)
                    ->where('receipt_number', 'like', $prefix . '%')
                    ->count(),
                'purchase_order' => (int) DB::table('purchase_orders')
                    ->where('store_id', $store->id)
                    ->where('po_number', 'like', $prefix . '%')
                    ->count(),
                'purchase_return' => (int) DB::table('purchase_returns')
                    ->where('store_id', $store->id)
                    ->where('return_number', 'like', $prefix . '%')
                    ->count(),
                default => 0,
            };
        } catch (\Throwable) {
            return 0;
        }
    }
}
