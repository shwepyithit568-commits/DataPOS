<?php

namespace App\POS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * PO Payment Log — records every payment event against a PurchaseOrder.
 *
 * @property int         $id
 * @property int         $store_id
 * @property int         $purchase_order_id
 * @property int|null    $supplier_id
 * @property string      $amount
 * @property string|null $reference
 * @property array|null  $slip_images   JSON array of storage-relative paths
 * @property int|null    $paid_by
 * @property \Carbon\Carbon $paid_at
 */
class PoPaymentLog extends Model
{
    protected $table = 'po_payment_logs';

    protected $fillable = [
        'store_id',
        'purchase_order_id',
        'supplier_id',
        'amount',
        'reference',
        'slip_images',
        'paid_by',
        'paid_at',
    ];

    protected $casts = [
        'slip_images' => 'array',
        'amount'      => 'decimal:2',
        'paid_at'     => 'datetime',
    ];

    /* ------------------------------------------------------------------ */
    /*  Relationships                                                        */
    /* ------------------------------------------------------------------ */

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Return public URLs for all slip images.
     *
     * @return array<string>
     */
    public function slipImageUrls(): array
    {
        if (empty($this->slip_images)) {
            return [];
        }

        return array_map(
            fn ($path) => asset('storage/' . ltrim($path, '/')),
            $this->slip_images
        );
    }
}
