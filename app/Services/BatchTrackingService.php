<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BatchTrackingService
{
    /**
     * Allocate inventory from batches using First-Expired, First-Out (FEFO) policy.
     *
     * @param Product $product
     * @param int|null $branchId
     * @param float $requiredQuantity
     * @return array<int, array{batch_id: int, batch_number: string, allocated_qty: float, expiration_date: string}>
     *
     * @throws ValidationException
     */
    public function allocateFefoBatches(Product $product, ?int $branchId, float $requiredQuantity): array
    {
        if ($requiredQuantity <= 0) {
            return [];
        }

        $query = ProductBatch::where('product_id', $product->id)
            ->where('status', 'active')
            ->where('available_quantity', '>', 0)
            ->where('expiration_date', '>', now()->startOfDay())
            ->orderBy('expiration_date', 'asc');

        if ($branchId) {
            $query->where(function ($q) use ($branchId) {
                $q->where('branch_id', $branchId)->orWhereNull('branch_id');
            });
        }

        $batches = $query->get();
        $allocations = [];
        $remainingToAllocate = $requiredQuantity;

        foreach ($batches as $batch) {
            $available = (float) $batch->available_quantity;
            if ($available <= 0) {
                continue;
            }

            $take = min($remainingToAllocate, $available);
            $expString = \Carbon\Carbon::parse($batch->expiration_date)->format('Y-m-d');
            $allocations[] = [
                'batch_id'        => $batch->id,
                'batch_number'    => $batch->batch_number,
                'allocated_qty'   => round($take, 4),
                'expiration_date' => $expString,
            ];

            $remainingToAllocate -= $take;
            if ($remainingToAllocate <= 0) {
                break;
            }
        }

        if ($remainingToAllocate > 0) {
            throw ValidationException::withMessages([
                'stock' => "မလုံလောက်သော FEFO Batch လက်ကျန်ဖြစ်နေပါသည် (လိုအပ်ချက်: {$requiredQuantity}, ရရှိနိုင်သော သက်တမ်းရှိ လက်ကျန်: " . ($requiredQuantity - $remainingToAllocate) . ")",
            ]);
        }

        return $allocations;
    }

    /**
     * Validate whether a batch is eligible for POS counter sale.
     * Strictly blocks expired or non-active batches.
     *
     * @throws ValidationException
     */
    public function validateBatchForSale(ProductBatch $batch): void
    {
        if ($batch->status !== 'active') {
            throw ValidationException::withMessages([
                'batch' => "Batch [{$batch->batch_number}] သည် Active အခြေအနေ မဟုတ်ပါ (Status: {$batch->status})။",
            ]);
        }

        if ($batch->isExpired()) {
            $expStr = \Carbon\Carbon::parse($batch->expiration_date)->format('Y-m-d');
            throw ValidationException::withMessages([
                'batch' => "သက်တမ်းကုန်ဆုံးပြီးဖြစ်သော Batch [{$batch->batch_number}] အား ရောင်းချခွင့် ပိတ်ပင်ထားပါသည် (Expiry: {$expStr})။",
            ]);
        }
    }

    /**
     * Get list of batches expiring soon across the store.
     */
    public function getExpiringBatches(Store $store, int $days = 30): Collection
    {
        $threshold = now()->addDays($days)->endOfDay();

        return ProductBatch::where('store_id', $store->id)
            ->where('status', 'active')
            ->where('available_quantity', '>', 0)
            ->where('expiration_date', '<=', $threshold)
            ->with(['product', 'branch', 'warehouse'])
            ->orderBy('expiration_date', 'asc')
            ->get();
    }

    /**
     * Generate lot tracing report for product recall.
     */
    public function getBatchRecallReport(Store $store, string $batchNumber): array
    {
        $batches = ProductBatch::where('store_id', $store->id)
            ->where('batch_number', trim($batchNumber))
            ->with(['product', 'branch', 'warehouse'])
            ->get();

        return [
            'batch_number'        => trim($batchNumber),
            'total_lots_found'    => $batches->count(),
            'total_initial_qty'   => (float) $batches->sum('initial_quantity'),
            'total_available_qty' => (float) $batches->sum('available_quantity'),
            'lots'                => $batches->map(fn (ProductBatch $b) => [
                'id'                 => $b->id,
                'product_name'       => $b->product?->name,
                'branch'             => $b->branch?->name ?? 'Default Branch',
                'warehouse'          => $b->warehouse?->name ?? 'Main Warehouse',
                'manufacture_date'   => $b->manufacture_date ? \Carbon\Carbon::parse($b->manufacture_date)->format('Y-m-d') : null,
                'expiration_date'    => \Carbon\Carbon::parse($b->expiration_date)->format('Y-m-d'),
                'initial_quantity'   => (float) $b->initial_quantity,
                'available_quantity' => (float) $b->available_quantity,
                'status'             => $b->status,
                'is_expired'         => $b->isExpired(),
            ])->toArray(),
        ];
    }
}
