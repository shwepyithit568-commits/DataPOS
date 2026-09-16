<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use App\POS\Services\DocumentSequenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'store_id',
        'transfer_number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'notes',
        'shipped_at',
        'received_at',
        'created_by',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Generate a unique transfer number for the store.
     */
    public static function generateNumber(int $storeId): string
    {
        // Shared, row-locked sequence — see DocumentSequenceService.
        return app(DocumentSequenceService::class)->nextNumber(
            Store::findOrFail($storeId),
            'stock_transfer'
        );
    }
}
