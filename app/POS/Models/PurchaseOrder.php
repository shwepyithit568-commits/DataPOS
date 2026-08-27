<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Purchase order document (alinthit_pos style — Phase 4 early build).
 *
 * Lifecycle: pending → ordered → received | cancelled
 * Payment:  unpaid → partial → paid
 *
 * The PO is a planning document: creating or ordering does NOT increase stock.
 * Only "receive" posts purchase_received ledger movements via the GoodsReceipt
 * infrastructure (SoT §6, §11.5).
 *
 * On receive, the PO's remaining_balance (total_cost - paid_amount) is added to
 * the supplier's total_credit. Payments (partial or full) reduce paid_amount and
 * the supplier's total_repaid, and update payment_status accordingly.
 *
 * Immutable once received: corrections use ledger reversals (SoT §15.1).
 */
class PurchaseOrder extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURNED = 'returned';

    public const PAYMENT_UNPAID = 'unpaid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_PAID = 'paid';

    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'supplier_id',
        'po_number',
        'status',
        'payment_status',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'total_quantity',
        'total_cost',
        'paid_amount',
        'remaining_balance',
        'reference',
        'notes',
        'voucher_images',
        'ordered_at',
        'received_at',
        'cancelled_at',
        'created_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'total_quantity' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'voucher_images' => 'array',
        'ordered_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Return full URLs for attached voucher photos / invoices.
     *
     * @return array<int, string>
     */
    public function getVoucherImageUrlsAttribute(): array
    {
        $images = $this->voucher_images ?? [];
        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($path) {
            if (! $path) return null;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return asset('storage/' . ltrim($path, '/'));
        }, $images)));
    }

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                      */
    /* ------------------------------------------------------------------ */

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * All purchase returns for this PO.
     */
    public function purchaseReturns(): HasMany
    {
        return $this->hasMany(\App\POS\Models\PurchaseReturn::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Status helpers                                                      */
    /* ------------------------------------------------------------------ */

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOrdered(): bool
    {
        return $this->status === self::STATUS_ORDERED;
    }

    public function isReceived(): bool
    {
        return $this->status === self::STATUS_RECEIVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    /* ------------------------------------------------------------------ */
    /*  Payment helpers                                                     */
    /* ------------------------------------------------------------------ */

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isUnpaid(): bool
    {
        return $this->payment_status === self::PAYMENT_UNPAID;
    }

    public function isPartiallyPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PARTIAL;
    }

    /**
     * True when a payment can still be applied (has remaining balance).
     */
    public function canReceivePayment(): bool
    {
        return $this->isReceived()
            && in_array($this->payment_status, [self::PAYMENT_UNPAID, self::PAYMENT_PARTIAL], true)
            && bccomp((string) $this->remaining_balance, '0', 2) === 1;
    }
}
