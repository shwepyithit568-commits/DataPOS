<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Store-scoped supplier master data. Seeded/imported during the AlinnThit
 * pilot; goods receipts reference suppliers by name/phone today and will
 * link to this table for purchasing/payables in the Operations phase.
 *
 * Debt tracking (AlinThit POS parity):
 *   - total_credit: cumulative amount owed to this supplier
 *   - total_repaid: cumulative payments made to this supplier
 *   - remaining_balance = total_credit - total_repaid (accessor)
 */
class Supplier extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'contact_person',
        'address',
        'notes',
        'total_credit',
        'total_repaid',
    ];

    protected $casts = [
        'total_credit' => 'decimal:2',
        'total_repaid' => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * All purchase orders for this supplier (any status).
     */
    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(\App\POS\Models\PurchaseOrder::class);
    }

    /**
     * Purchase orders still owing money (unpaid or partial).
     * Ordered oldest-first so FIFO payment application is trivial.
     */
    public function unpaidPurchaseOrders(): HasMany
    {
        return $this->hasMany(\App\POS\Models\PurchaseOrder::class)
            ->where('status', 'received')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('remaining_balance', '>', 0)
            ->orderBy('created_at', 'asc');
    }

    /**
     * remaining_balance = total_credit - total_repaid.
     */
    public function getRemainingBalanceAttribute(): string
    {
        return bcsub((string) $this->total_credit, (string) $this->total_repaid, 2);
    }

    /**
     * True when money is still owed to this supplier.
     */
    public function getHasOutstandingBalanceAttribute(): bool
    {
        return bccomp($this->remaining_balance, '0', 2) === 1;
    }

    /**
     * Recompute total_credit / total_repaid from linked POs (idempotent).
     * Supplier can be updated by name+phone across imports, so this reconciles
     * the cached columns without relying on incremental deltas.
     */
    public function recalculateBalances(): void
    {
        // total_credit = cumulative purchase cost for all received POs
        $credit = $this->purchaseOrders()
            ->where('status', 'received')
            ->sum('total_cost');

        // total_repaid = cumulative payments made against received POs
        $repaid = $this->purchaseOrders()
            ->where('status', 'received')
            ->sum('paid_amount');

        $this->update([
            'total_credit' => $credit ?: 0,
            'total_repaid' => $repaid ?: 0,
        ]);
    }
}
