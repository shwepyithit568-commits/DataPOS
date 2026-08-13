<?php

namespace App\POS\Enums;

/**
 * Movement types from Source_of_Truth_MM.md §5 (minimum set) + `reversal` for corrections
 * (SoT §15.1 — posted movements are corrected with reversal movements only).
 *
 * Sign convention:
 *   inbound  (+)  — increases on-hand
 *   outbound (−)  — decreases on-hand
 *   zero     (0)  — record-only (online_confirm: reserve → committed, no double deduction)
 *   reversal (±)  — mirrors the reversed movement's sign
 */
enum InventoryMovementType: string
{
    case OpeningBalance = 'opening_balance';
    case PurchaseReceived = 'purchase_received';
    case PurchaseReturned = 'purchase_returned';
    case PosSale = 'pos_sale';
    case SalesReturn = 'sales_return';
    case ExchangeReturn = 'exchange_return';
    case ExchangeSale = 'exchange_sale';
    case ServiceConsumption = 'service_consumption';
    case ServicePartReturn = 'service_part_return';
    case TransferOut = 'transfer_out';
    case TransferIn = 'transfer_in';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case InternalUse = 'internal_use';
    case OnlineReserve = 'online_reserve';
    case OnlineConfirm = 'online_confirm';
    case OnlineCancel = 'online_cancel';
    case Reversal = 'reversal';

    /**
     * Expected sign of quantity_delta for this movement type:
     * 1 = inbound (+), -1 = outbound (−), 0 = no quantity change, null = reversal (any sign).
     */
    public function expectedSign(): ?int
    {
        return match ($this) {
            self::OpeningBalance,
            self::PurchaseReceived,
            self::SalesReturn,
            self::ExchangeReturn,
            self::TransferIn,
            self::AdjustmentIn,
            self::OnlineCancel,
            self::ServicePartReturn => 1,

            self::PurchaseReturned,
            self::PosSale,
            self::ExchangeSale,
            self::TransferOut,
            self::AdjustmentOut,
            self::InternalUse,
            self::ServiceConsumption,
            self::OnlineReserve => -1,

            self::OnlineConfirm => 0,

            self::Reversal => null,
        };
    }

    /** True when this type is one of the online-order lifecycle events. */
    public function isOnlineOrderEvent(): bool
    {
        return in_array($this, [self::OnlineReserve, self::OnlineConfirm, self::OnlineCancel], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::OpeningBalance => 'Opening balance',
            self::PurchaseReceived => 'Purchase received',
            self::PurchaseReturned => 'Purchase returned',
            self::PosSale => 'POS sale',
            self::SalesReturn => 'Sales return',
            self::ExchangeReturn => 'Exchange return',
            self::ExchangeSale => 'Exchange sale',
            self::ServiceConsumption => 'Service consumption',
            self::ServicePartReturn => 'Service part return',
            self::TransferOut => 'Transfer out',
            self::TransferIn => 'Transfer in',
            self::AdjustmentIn => 'Adjustment in',
            self::AdjustmentOut => 'Adjustment out',
            self::InternalUse => 'Internal use',
            self::OnlineReserve => 'Online reserve',
            self::OnlineConfirm => 'Online confirm',
            self::OnlineCancel => 'Online cancel',
            self::Reversal => 'Reversal',
        };
    }
}
