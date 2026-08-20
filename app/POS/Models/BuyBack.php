<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyBack extends Model
{
    protected $fillable = [
        'store_id',
        'buyback_number',
        'customer_id',
        'pos_sale_id',
        'total_value',
        'refund_amount',
        'status',
        'reason',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_value' => 'decimal:4',
        'refund_amount' => 'decimal:4',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(BuyBackItem::class);
    }

    /**
     * Generate a unique buyback number for the store.
     */
    public static function generateNumber(int $storeId): string
    {
        $date = now()->format('Ymd');
        $prefix = "BB-{$date}-";

        $last = static::where('store_id', $storeId)
            ->where('buyback_number', 'like', "{$prefix}%")
            ->orderByDesc('buyback_number')
            ->value('buyback_number');

        $seq = $last ? (int) substr($last, -4) + 1 : 1;

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
