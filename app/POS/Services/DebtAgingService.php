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

        // Calculate aggregate store KPIs. Running totals are decimal strings —
        // these figures appear on the aging report and in its CSV export.
        $totalOutstanding = '0.00';
        $bucket0To30 = '0.00';
        $bucket31To60 = '0.00';
        $bucket61To90 = '0.00';
        $bucket90Plus = '0.00';
        $highRiskCount = 0;

        foreach ($customersWithDebt as $c) {
            $totalOutstanding = bcadd($totalOutstanding, (string) $c['total_due'], 2);
            $bucket0To30 = bcadd($bucket0To30, (string) $c['bucket_0_30'], 2);
            $bucket31To60 = bcadd($bucket31To60, (string) $c['bucket_31_60'], 2);
            $bucket61To90 = bcadd($bucket61To90, (string) $c['bucket_61_90'], 2);
            $bucket90Plus = bcadd($bucket90Plus, (string) $c['bucket_90_plus'], 2);

            if (bccomp((string) $c['bucket_61_90'], '0', 2) > 0 || bccomp((string) $c['bucket_90_plus'], '0', 2) > 0) {
                $highRiskCount++;
            }
        }

        $hasOutstanding = bccomp($totalOutstanding, '0', 2) > 0;

        $metrics = [
            'total_outstanding'     => $totalOutstanding,
            'bucket_0_30'           => $bucket0To30,
            'bucket_31_60'          => $bucket31To60,
            'bucket_61_90'          => $bucket61To90,
            'bucket_90_plus'        => $bucket90Plus,
            'total_debtors'         => count($customersWithDebt),
            'high_risk_debtors'     => $highRiskCount,
            // Percentages are proportions, not money — float is correct here.
            'pct_current'           => $hasOutstanding ? round(((float) $bucket0To30 / (float) $totalOutstanding) * 100, 1) : 0,
            'pct_overdue'           => $hasOutstanding ? round((((float) $bucket61To90 + (float) $bucket90Plus) / (float) $totalOutstanding) * 100, 1) : 0,
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
            $totalBalance = bc_sum($entries->pluck('amount'));
            if (bccomp($totalBalance, '0.001', 3) <= 0) {
                continue; // No outstanding debt
            }

            $customer = $entries->first()->customer;
            $customerName = $customer?->name ?? "Customer #{$customerId}";
            $customerPhone = $customer?->phone ?? '-';

            $totalPaid = ltrim(bc_sum($entries->filter(fn ($e) => bccomp((string) $e->amount, '0', 2) < 0)->pluck('amount')), '-');
            $debitEntries = $entries->filter(fn ($e) => bccomp((string) $e->amount, '0', 2) > 0);

            // The FIFO consumption below decides which portion of each debit is
            // still unpaid and how it ages — the numbers printed on the report
            // and exported to CSV, so they are accumulated with bcmath.
            $bucket0To30 = '0.00';
            $bucket31To60 = '0.00';
            $bucket61To90 = '0.00';
            $bucket90Plus = '0.00';

            $oldestUnpaidDate = null;
            $maxOverdueDays = 0;
            $remainingPaidToConsume = $totalPaid;

            foreach ($debitEntries as $debit) {
                $debitAmount = bcadd((string) $debit->amount, '0', 2);
                if (bccomp($debitAmount, '0', 2) <= 0) {
                    continue;
                }

                if (bccomp($remainingPaidToConsume, $debitAmount, 2) >= 0) {
                    $remainingPaidToConsume = bcsub($remainingPaidToConsume, $debitAmount, 2);
                    continue; // Fully covered by earlier/subsequent payment
                }

                $unpaidPortion = bcsub($debitAmount, $remainingPaidToConsume, 2);
                $remainingPaidToConsume = '0.00';

                $occurredAt = $debit->occurred_at ?? $debit->created_at ?? $now;
                $days = max(0, $occurredAt->diffInDays($now));

                if ($oldestUnpaidDate === null || $occurredAt->lt($oldestUnpaidDate)) {
                    $oldestUnpaidDate = $occurredAt;
                    $maxOverdueDays = $days;
                }

                if ($days <= 30) {
                    $bucket0To30 = bcadd($bucket0To30, $unpaidPortion, 2);
                } elseif ($days <= 60) {
                    $bucket31To60 = bcadd($bucket31To60, $unpaidPortion, 2);
                } elseif ($days <= 90) {
                    $bucket61To90 = bcadd($bucket61To90, $unpaidPortion, 2);
                } else {
                    $bucket90Plus = bcadd($bucket90Plus, $unpaidPortion, 2);
                }
            }

            // Determine Risk Level
            $riskLevel = 'low';
            if (bccomp($bucket90Plus, '0', 2) > 0) {
                $riskLevel = 'critical';
            } elseif (bccomp($bucket61To90, '0', 2) > 0) {
                $riskLevel = 'high';
            } elseif (bccomp($bucket31To60, '0', 2) > 0) {
                $riskLevel = 'medium';
            }

            $results[] = [
                'customer_id'        => $customerId,
                'customer_name'      => $customerName,
                'customer_phone'     => $customerPhone,
                'total_due'          => bcadd($totalBalance, '0', 2),
                'bucket_0_30'        => bcadd($bucket0To30, '0', 2),
                'bucket_31_60'       => bcadd($bucket31To60, '0', 2),
                'bucket_61_90'       => bcadd($bucket61To90, '0', 2),
                'bucket_90_plus'     => bcadd($bucket90Plus, '0', 2),
                'oldest_unpaid_date' => $oldestUnpaidDate?->toDateString(),
                'max_overdue_days'   => $maxOverdueDays,
                'risk_level'         => $riskLevel,
            ];
        }

        return $results;
    }
}
