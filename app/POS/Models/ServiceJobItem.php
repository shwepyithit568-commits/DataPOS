<?php

namespace App\POS\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Line item on a service job — a repair service (labour) or a spare part (§16).
 *
 * Part rows that reference a product can be stock-deducted through the
 * ledger (`service_consumption`). `is_deducted` is a one-way flag: once a
 * part is consumed it stays consumed (corrections go through the ledger).
 */
class ServiceJobItem extends Model
{
    public const TYPES = ['service', 'part'];

    protected $fillable = [
        'service_job_id',
        'item_type',
        'name',
        'sku',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
        'is_deducted',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'is_deducted' => 'boolean',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function isPart(): bool
    {
        return $this->item_type === 'part';
    }

    public function isService(): bool
    {
        return $this->item_type === 'service';
    }
}
