<?php

namespace App\POS\Models;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\User;
use App\POS\Enums\InventoryMovementType;
use App\POS\Exceptions\InventoryException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One immutable row in the shared inventory ledger (SoT §5).
 *
 * Posted movements can never be edited or deleted — corrections are posted as
 * `reversal` movements linked via `reversal_of_id` (SoT §15.1). This is enforced
 * at the model level so no caller can bypass it.
 */
class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    protected $fillable = [
        'store_id',
        'branch_id',
        'warehouse_id',
        'product_id',
        'product_variant_id',
        'movement_type',
        'quantity_delta',
        'unit_cost',
        'source_type',
        'source_id',
        'client_transaction_id',
        'occurred_at',
        'posted_by',
        'reversal_of_id',
        'metadata',
    ];

    protected $casts = [
        'quantity_delta' => 'decimal:3',
        'unit_cost' => 'decimal:4',
        'occurred_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new InventoryException('Inventory movements are immutable — post a reversal movement instead.');
        });

        static::deleting(function () {
            throw new InventoryException('Inventory movements cannot be deleted — post a reversal movement instead.');
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedMovement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function type(): InventoryMovementType
    {
        return InventoryMovementType::from($this->movement_type);
    }
}
