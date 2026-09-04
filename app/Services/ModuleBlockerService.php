<?php

namespace App\Services;

use App\Capabilities\Capability;
use App\Models\Order;
use App\Models\Store;
use App\Models\Supplier;
use App\POS\Models\CashierShift;
use App\POS\Models\CustomerLedgerEntry;
use App\POS\Models\ServiceJob;
use App\POS\Models\StockCount;
use App\POS\Models\StockTransfer;

class ModuleBlockerService
{
    /**
     * Get active blockers preventing a capability from being disabled.
     *
     * @return array<int, array{domain: string, count: int|float, message_key: string}>
     */
    public function getBlockersForCapability(Store $store, string $capability): array
    {
        $blockers = [];

        switch ($capability) {
            case Capability::STOREFRONT_ECOMMERCE:
            case Capability::STOREFRONT_ONLINE_ORDERING:
                $pendingOrders = Order::where('store_id', $store->id)
                    ->whereIn('status', ['pending_contact', 'confirmed', 'processing'])
                    ->count();

                if ($pendingOrders > 0) {
                    $blockers[] = [
                        'domain' => 'orders',
                        'count' => $pendingOrders,
                        'message_key' => 'messages.blocker_pending_orders',
                    ];
                }
                break;

            case Capability::OPERATIONS_CASHIER_SHIFTS:
                $openShifts = CashierShift::where('store_id', $store->id)
                    ->where('status', 'open')
                    ->count();

                if ($openShifts > 0) {
                    $blockers[] = [
                        'domain' => 'cashier_shifts',
                        'count' => $openShifts,
                        'message_key' => 'messages.blocker_open_cashier_shifts',
                    ];
                }
                break;

            case Capability::SERVICE_REPAIR_JOBS:
                $activeJobs = ServiceJob::where('store_id', $store->id)
                    ->whereNotIn('status', ['delivered', 'cancelled', 'unrepairable'])
                    ->count();

                if ($activeJobs > 0) {
                    $blockers[] = [
                        'domain' => 'service_jobs',
                        'count' => $activeJobs,
                        'message_key' => 'messages.blocker_active_repair_jobs',
                    ];
                }
                break;

            case Capability::INVENTORY_TRANSFERS:
                $activeTransfers = StockTransfer::where('store_id', $store->id)
                    ->whereIn('status', ['pending', 'in_transit'])
                    ->count();

                if ($activeTransfers > 0) {
                    $blockers[] = [
                        'domain' => 'stock_transfers',
                        'count' => $activeTransfers,
                        'message_key' => 'messages.blocker_active_stock_transfers',
                    ];
                }
                break;

            case Capability::INVENTORY_STOCK_AUDIT:
                $activeCounts = StockCount::where('store_id', $store->id)
                    ->whereIn('status', ['draft', 'in_progress'])
                    ->count();

                if ($activeCounts > 0) {
                    $blockers[] = [
                        'domain' => 'stock_counts',
                        'count' => $activeCounts,
                        'message_key' => 'messages.blocker_active_stock_counts',
                    ];
                }
                break;

            case Capability::COMMERCE_CUSTOMER_DEBT:
                $totalDebt = (float) CustomerLedgerEntry::where('store_id', $store->id)->sum('amount');

                if ($totalDebt > 0) {
                    $blockers[] = [
                        'domain' => 'customer_debt',
                        'count' => $totalDebt,
                        'message_key' => 'messages.blocker_outstanding_customer_debt',
                    ];
                }
                break;

            case Capability::COMMERCE_SUPPLIER_PAYABLES:
                $debtSuppliers = Supplier::where('store_id', $store->id)
                    ->whereRaw('(total_credit - total_repaid) > 0')
                    ->count();

                if ($debtSuppliers > 0) {
                    $blockers[] = [
                        'domain' => 'supplier_payables',
                        'count' => $debtSuppliers,
                        'message_key' => 'messages.blocker_outstanding_supplier_payables',
                    ];
                }
                break;
        }

        return $blockers;
    }

    /**
     * Get active blockers preventing a sales channel from being disabled.
     *
     * @return array<int, array{domain: string, count: int|float, message_key: string}>
     */
    public function getBlockersForChannel(Store $store, string $channel): array
    {
        $blockers = [];

        if ($channel === Store::CHANNEL_ONLINE_STORE || $channel === Store::CHANNEL_ONLINE_ORDERING) {
            $pendingOrders = Order::where('store_id', $store->id)
                ->whereIn('status', ['pending_contact', 'confirmed', 'processing'])
                ->count();

            if ($pendingOrders > 0) {
                $blockers[] = [
                    'domain' => 'orders',
                    'count' => $pendingOrders,
                    'message_key' => 'messages.blocker_pending_orders',
                ];
            }
        }

        return $blockers;
    }

    /**
     * Determine if a capability can safely be disabled.
     */
    public function canDisableCapability(Store $store, string $capability): bool
    {
        return empty($this->getBlockersForCapability($store, $capability));
    }

    /**
     * Determine if a sales channel can safely be disabled.
     */
    public function canDisableChannel(Store $store, string $channel): bool
    {
        return empty($this->getBlockersForChannel($store, $channel));
    }
}
