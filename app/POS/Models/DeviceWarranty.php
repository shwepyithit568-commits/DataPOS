<?php

namespace App\POS\Models;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceWarranty extends Model
{
    use HasFactory;

    protected $table = 'device_warranties';

    protected $fillable = [
        'store_id',
        'product_id',
        'product_name',
        'customer_id',
        'customer_name',
        'customer_phone',
        'serial_number',
        'imei_primary',
        'imei_secondary',
        'invoice_number',
        'pos_sale_id',
        'purchase_date',
        'warranty_duration_months',
        'warranty_expiry_date',
        'warranty_type',
        'status',
        'terms_conditions',
        'notes',
        'claim_count',
        'last_claimed_at',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expiry_date' => 'date',
        'last_claimed_at' => 'datetime',
        'warranty_duration_months' => 'integer',
        'claim_count' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(PosSale::class, 'pos_sale_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $term = trim($term);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('serial_number', 'like', "%{$term}%")
                ->orWhere('imei_primary', 'like', "%{$term}%")
                ->orWhere('imei_secondary', 'like', "%{$term}%")
                ->orWhere('invoice_number', 'like', "%{$term}%")
                ->orWhere('product_name', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$term}%");
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status) || $status === 'all') {
            return $query;
        }

        if ($status === 'expiring_soon') {
            return $query->where('status', 'active')
                ->whereBetween('warranty_expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
        }

        return $query->where('status', $status);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->warranty_expiry_date->isPast() || $this->status === 'expired';
    }

    public function getDaysRemainingAttribute(): int
    {
        if ($this->warranty_expiry_date->isPast()) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->warranty_expiry_date->endOfDay(), false);
    }

    public function getComputedStatusAttribute(): string
    {
        if ($this->status === 'void' || $this->status === 'claimed') {
            return $this->status;
        }

        if ($this->warranty_expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->days_remaining <= 30) {
            return 'expiring_soon';
        }

        return 'active';
    }
}
