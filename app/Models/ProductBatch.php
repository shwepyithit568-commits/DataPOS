<?php

namespace App\Models;

use App\POS\Models\Branch;
use App\POS\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'product_id',
        'branch_id',
        'warehouse_id',
        'batch_number',
        'manufacture_date',
        'expiration_date',
        'initial_quantity',
        'available_quantity',
        'cost_price',
        'status',
    ];

    protected $casts = [
        'manufacture_date'   => 'date',
        'expiration_date'    => 'date',
        'initial_quantity'   => 'decimal:4',
        'available_quantity' => 'decimal:4',
        'cost_price'         => 'decimal:2',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Check if batch has expired.
     */
    public function isExpired(): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        $date = \Carbon\Carbon::parse($this->expiration_date)->startOfDay();
        return $date->lte(now()->startOfDay());
    }

    /**
     * Check if batch expires within given days.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if (! $this->expiration_date) {
            return false;
        }

        if ($this->isExpired()) {
            return true;
        }

        $date = \Carbon\Carbon::parse($this->expiration_date)->startOfDay();
        return $date->lte(now()->addDays($days)->endOfDay());
    }
}
