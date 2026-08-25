<?php

namespace App\POS\Services;

use App\Models\Store;
use App\Models\User;
use App\POS\Models\CustomerLedgerEntry;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DebtAgingService
{
    /**
     * Compute comprehensive debt aging analysis for a store.
     */
    public function getAgingAnalysis(Store $store, array $filters = [], int|string $perPage = 25): array
    {
        $customersWithDebt = $this->calculateAllCustomerAging($store);

        // Calculate aggregate store KPIs
        $totalOutstanding = 0.0;
        $bucket0To30 = 0.0;
        $bucket31To60 = 0.0;
        $bucket61To90 = 0.0;
        $bucket90Plus = 0.0;
        $highRiskCount = 0;

        foreach ($customersWithDebt as $c) {
            $totalOutstanding += $c['total_due'];
            $bucket0To30 += $c['bucket_0_30'];
            $bucket31To60 += $c['bucket_31_60'];
            $bucket61To90 += $c['bucket_61_90'];
            $bucket90Plus += $c['bucket_90_plus'];

            if ($c['bucket_61_90'] > 0 || $c['bucket_90_plus'] > 0) {
                $highRiskCount++;
            }
        }

        $metrics = [
            'total_outstanding'     => $totalOutstanding,
            'bucket_0_30'           => $bucket0To30,
            'bucket_31_60'          => $bucket31To60,
            'bucket_61_90'          => $bucket61To90,
            'bucket_90_plus'        => $bucket90Plus,
            'total_debtors'         => count($customersWithDebt),
            'high_risk_debtors'     => $highRiskCount,
            'pct_current'           => $totalOutstanding > 0 ? round(($bucket0To30 / $totalOutstanding) * 100, 1) : 0,
            'pct_overdue'           => $totalOutstanding > 0 ? round((($bucket61To90 + $bucket90Plus) / $totalOutstanding) * 100, 1) : 0,
        ];

        // Apply filters
        $filteredCollection = collect($customersWithDebt);

        if (!empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $filteredCollection = $filteredCollection->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['customer_name']), $search)
                    || str_contains($item['customer_phone'] ?? '', $search);
            });
        }

        if (!empty($filters['bucket'])) {
            $bucket = $filters['bucket'];
            $filteredCollection = $filteredCollection->filter(function ($item) use ($bucket) {
                return match ($bucket) {
                    '0_30'    => $item['bucket_0_30'] > 0,
                    '31_60'   => $item['bucket_31_60'] > 0,
                    '61_90'   => $item['bucket_61_90'] > 0,
                    '90_plus' => $item['bucket_90_plus'] > 0,
                    default   => true,
                };
            });
        }

        if (!empty($filters['risk'])) {
            $risk = $filters['risk'];
            $filteredCollection = $filteredCollection->filter(fn ($item) => $item['risk_level'] === $risk);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'total_due_desc';
        $sorted = match ($sort) {
            'total_due_asc'      => $filteredCollection->sortBy('total_due'),
            'overdue_days_desc'  => $filteredCollection->sortByDesc('max_overdue_days'),
            'overdue_days_asc'   => $filteredCollection->sortBy('max_overdue_days'),
            'bucket_90_desc'     => $filteredCollection->sortByDesc('bucket_90_plus'),
            'name_asc'           => $filteredCollection->sortBy('customer_name', SORT_NATURAL | SORT_FLAG_CASE),
            default              => $filteredCollection->sortByDesc('total_due'),
        };

        if ($perPage === 'all' || (int) $perPage === 0) {
            $paginated = $sorted->values();
        } else {
            $page = (int) ($filters['page'] ?? 1);
            $perPageInt = (int) $perPage;
            $items = $sorted->forPage($page, $perPageInt)->values();

            $paginated = new LengthAwarePaginator(
                $items,
                $sorted->count(),
                $perPageInt,
                $page,
                ['path' => request()->url(), 'query' => request()->query()]
            );
        }

        return [
            'metrics'   => $metrics,
            'customers' => $paginated,
        ];
    }

    /**
     * Calculate FIFO debt aging for each customer with positive balance.
     *
     * @return array<int, array>
     */
    protected function calculateAllCustomerAging(Store $store): array
    {
        $allEntries = CustomerLedgerEntry::where('store_id', $store->id)
            ->with('customer')
            ->orderBy('occurred_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $grouped = $allEntries->groupBy('customer_id');
        $now = now();
        $results = [];

        foreach ($grouped as $customerId => $entries) {
            $totalBalance = (float) $entries->sum('amount');
            if ($totalBalance <= 0.001) {
                continue; // No outstanding debt
            }

            $customer = $entries->first()->customer;
            $customerName = $customer?->name ?? "Customer #{$customerId}";
            $customerPhone = $customer?->phone ?? '-';

            $totalPaid = (float) abs($entries->filter(fn ($e) => (float) $e->amount < 0)->sum('amount'));
            $debitEntries = $entries->filter(fn ($e) => (float) $e->amount > 0);

            $bucket0To30 = 0.0;
            $bucket31To60 = 0.0;
            $bucket61To90 = 0.0;
            $bucket90Plus = 0.0;

            $oldestUnpaidDate = null;
            $maxOverdueDays = 0;
            $remainingPaidToConsume = $totalPaid;

            foreach ($debitEntries as $debit) {
                $debitAmount = (float) $debit->amount;
                if ($debitAmount <= 0) {
                    continue;
                }

                if ($remainingPaidToConsume >= $debitAmount) {
                    $remainingPaidToConsume -= $debitAmount;
                    continue; // Fully covered by earlier/subsequent payment
                }

                $unpaidPortion = $debitAmount - $remainingPaidToConsume;
                $remainingPaidToConsume = 0.0;

                $occurredAt = $debit->occurred_at ?? $debit->created_at ?? $now;
                $days = max(0, $occurredAt->diffInDays($now));

                if ($oldestUnpaidDate === null || $occurredAt->lt($oldestUnpaidDate)) {
                    $oldestUnpaidDate = $occurredAt;
                    $maxOverdueDays = $days;
                }

                if ($days <= 30) {
                    $bucket0To30 += $unpaidPortion;
                } elseif ($days <= 60) {
                    $bucket31To60 += $unpaidPortion;
                } elseif ($days <= 90) {
                    $bucket61To90 += $unpaidPortion;
                } else {
                    $bucket90Plus += $unpaidPortion;
                }
            }

            // Determine Risk Level
            $riskLevel = 'low';
            if ($bucket90Plus > 0) {
                $riskLevel = 'critical';
            } elseif ($bucket61To90 > 0) {
                $riskLevel = 'high';
            } elseif ($bucket31To60 > 0) {
                $riskLevel = 'medium';
            }

            $results[] = [
                'customer_id'        => $customerId,
                'customer_name'      => $customerName,
                'customer_phone'     => $customerPhone,
                'total_due'          => round($totalBalance, 2),
                'bucket_0_30'        => round($bucket0To30, 2),
                'bucket_31_60'       => round($bucket31To60, 2),
                'bucket_61_90'       => round($bucket61To90, 2),
                'bucket_90_plus'     => round($bucket90Plus, 2),
                'oldest_unpaid_date' => $oldestUnpaidDate?->toDateString(),
                'max_overdue_days'   => $maxOverdueDays,
                'risk_level'         => $riskLevel,
            ];
        }

        return $results;
    }
}
