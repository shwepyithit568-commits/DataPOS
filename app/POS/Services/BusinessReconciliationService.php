<?php

namespace App\POS\Services;

use App\Models\Store;
use App\POS\Enums\InventoryMovementType;
use App\POS\Models\CashierShift;
use App\POS\Models\InventoryBalance;
use App\POS\Models\InventoryMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BusinessReconciliationService
{
    /**
     * Compute the Stock Reconciliation Equation for a store and date range.
     *
     * Opening Stock
     * + Purchases Received + Sales Returns + Positive Adjustments + Transfers In
     * - POS Sales - Online Sales - Purchase Returns - Negative Adjustments - Transfers Out
     * = Calculated Closing Stock
     *
     * @return array{
     *   opening_stock: string,
     *   purchases_received: string,
     *   sales_returns: string,
     *   positive_adjustments: string,
     *   transfers_in: string,
     *   pos_sales: string,
     *   online_sales: string,
     *   purchase_returns: string,
     *   negative_adjustments: string,
     *   transfers_out: string,
     *   calculated_closing: string,
     *   actual_ledger_balance: string,
     *   discrepancy: string,
     *   is_clean: bool
     * }
     */
    public function stockReconciliation(Store $store, ?\DateTimeInterface $startDate = null, ?\DateTimeInterface $endDate = null): array
    {
        $start = Carbon::parse($startDate ?: now()->startOfDay());
        $end = Carbon::parse($endDate ?: now()->endOfDay());

        // 1. Opening stock: sum of balances prior to start date
        $priorNet = DB::table('inventory_movements')
            ->where('store_id', $store->id)
            ->where('occurred_at', '<', $start)
            ->sum('quantity_delta');

        $openingStock = number_format((float) $priorNet, 3, '.', '');

        // 2. Aggregate movements during the period
        $movements = DB::table('inventory_movements')
            ->where('store_id', $store->id)
            ->whereBetween('occurred_at', [$start, $end])
            ->select('movement_type', DB::raw('SUM(ABS(quantity_delta)) as total_qty'))
            ->groupBy('movement_type')
            ->pluck('total_qty', 'movement_type');

        $qty = fn (string $type) => number_format((float) ($movements[$type] ?? 0), 3, '.', '');

        $purchasesReceived   = $qty(InventoryMovementType::PurchaseReceived->value);
        $salesReturns        = bcadd($qty(InventoryMovementType::SalesReturn->value), $qty(InventoryMovementType::ExchangeReturn->value), 3);
        $positiveAdjustments = $qty(InventoryMovementType::AdjustmentIn->value);
        $transfersIn         = $qty(InventoryMovementType::TransferIn->value);

        $posSales            = bcadd($qty(InventoryMovementType::PosSale->value), $qty(InventoryMovementType::ExchangeSale->value), 3);
        $onlineSales         = $qty(InventoryMovementType::OnlineReserve->value);
        $purchaseReturns     = $qty(InventoryMovementType::PurchaseReturned->value);
        $negativeAdjustments = bcadd($qty(InventoryMovementType::AdjustmentOut->value), $qty(InventoryMovementType::InternalUse->value), 3);
        $transfersOut        = $qty(InventoryMovementType::TransferOut->value);

        // Calculate closing stock
        $inbound = bcadd(bcadd(bcadd($purchasesReceived, $salesReturns, 3), $positiveAdjustments, 3), $transfersIn, 3);
        $outbound = bcadd(bcadd(bcadd(bcadd($posSales, $onlineSales, 3), $purchaseReturns, 3), $negativeAdjustments, 3), $transfersOut, 3);

        $calculatedClosing = bcsub(bcadd($openingStock, $inbound, 3), $outbound, 3);

        // Actual ledger balance up to the end of the period
        $actualNet = DB::table('inventory_movements')
            ->where('store_id', $store->id)
            ->where('occurred_at', '<=', $end)
            ->sum('quantity_delta');

        $actualLedgerBalance = number_format((float) $actualNet, 3, '.', '');
        $discrepancy = bcsub($calculatedClosing, $actualLedgerBalance, 3);

        return [
            'opening_stock'        => $openingStock,
            'purchases_received'   => $purchasesReceived,
            'sales_returns'        => $salesReturns,
            'positive_adjustments' => $positiveAdjustments,
            'transfers_in'         => $transfersIn,
            'pos_sales'            => $posSales,
            'online_sales'         => $onlineSales,
            'purchase_returns'     => $purchaseReturns,
            'negative_adjustments' => $negativeAdjustments,
            'transfers_out'        => $transfersOut,
            'calculated_closing'   => $calculatedClosing,
            'actual_ledger_balance'=> $actualLedgerBalance,
            'discrepancy'          => $discrepancy,
            'is_clean'             => bccomp($discrepancy, '0', 3) === 0,
        ];
    }

    /**
     * Compute the Cash Drawer Reconciliation Equation for a store on a given business date.
     *
     * Opening Cash + Cash Sales + Customer Debt Collections + Other Cash In
     * - Cash Refunds - Expenses Paid - Supplier Payments - Other Cash Out
     * = Expected Closing Cash
     *
     * @return array{
     *   opening_cash: string,
     *   cash_sales: string,
     *   debt_collections: string,
     *   cash_in: string,
     *   cash_refunds: string,
     *   expenses_paid: string,
     *   supplier_payments: string,
     *   cash_out: string,
     *   expected_closing_cash: string,
     *   counted_cash: string,
     *   variance: string,
     *   has_variance: bool
     * }
     */
    public function cashReconciliation(Store $store, ?\DateTimeInterface $date = null): array
    {
        $targetDate = Carbon::parse($date ?: now());
        $start = $targetDate->copy()->startOfDay();
        $end = $targetDate->copy()->endOfDay();

        // 1. Drawer math from cashier shifts
        $shifts = CashierShift::query()
            ->where('store_id', $store->id)
            ->whereBetween('opened_at', [$start, $end])
            ->get();

        $openingCash = '0.00';
        $cashSales = '0.00';
        $cashRefunds = '0.00';
        $cashIn = '0.00';
        $cashOut = '0.00';
        $countedCash = '0.00';

        foreach ($shifts as $s) {
            $openingCash = bcadd($openingCash, (string) $s->opening_cash, 2);
            $cashSales = bcadd($cashSales, (string) $s->cash_sales, 2);
            $cashRefunds = bcadd($cashRefunds, (string) $s->cash_refunds, 2);
            $cashIn = bcadd($cashIn, (string) $s->cash_in, 2);
            $cashOut = bcadd($cashOut, (string) $s->cash_out, 2);
            $countedCash = bcadd($countedCash, (string) ($s->actual_closing_amount ?? '0'), 2);
        }

        // 2. Customer debt collections paid in cash during this date
        $debtCollections = number_format((float) DB::table('customer_ledger_entries')
            ->where('store_id', $store->id)
            ->where('entry_type', 'credit_payment')
            ->where('notes', 'like', '%cash%')
            ->whereBetween('created_at', [$start, $end])
            ->sum('credit_amount'), 2, '.', '');

        // 3. Cash expenses paid from cash drawer
        $expensesPaid = number_format((float) DB::table('expenses')
            ->where('store_id', $store->id)
            ->where('payment_method', 'cash')
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount'), 2, '.', '');

        // 4. Supplier PO payments paid in cash
        $supplierPayments = number_format((float) DB::table('po_payment_logs')
            ->where('store_id', $store->id)
            ->where('payment_method', 'cash')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount'), 2, '.', '');

        // Inflow
        $totalInflow = bcadd(bcadd(bcadd($openingCash, $cashSales, 2), $debtCollections, 2), $cashIn, 2);

        // Outflow
        $totalOutflow = bcadd(bcadd(bcadd($cashRefunds, $expensesPaid, 2), $supplierPayments, 2), $cashOut, 2);

        $expectedClosing = bcsub($totalInflow, $totalOutflow, 2);
        $variance = bcsub($countedCash, $expectedClosing, 2);

        return [
            'opening_cash'          => $openingCash,
            'cash_sales'            => $cashSales,
            'debt_collections'      => $debtCollections,
            'cash_in'               => $cashIn,
            'cash_refunds'          => $cashRefunds,
            'expenses_paid'         => $expensesPaid,
            'supplier_payments'     => $supplierPayments,
            'cash_out'              => $cashOut,
            'expected_closing_cash' => $expectedClosing,
            'counted_cash'          => $countedCash,
            'variance'              => $variance,
            'has_variance'          => bccomp($variance, '0.00', 2) !== 0,
        ];
    }
}
