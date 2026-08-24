<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'code',
        'type',
        'value',
        'min_order_amount',
        'category_id',
        'product_id',
        'total_uses_limit',
        'per_customer_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'value' => 'float',
        'min_order_amount' => 'float',
        'total_uses_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    // ---------- Relationships ----------

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    // ---------- Scopes ----------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    // ---------- Business Helpers ----------

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isNotStarted(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture();
    }

    public function isUsageLimitReached(): bool
    {
        return $this->total_uses_limit !== null && $this->used_count >= $this->total_uses_limit;
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }
        if ($this->isExpired()) {
            return 'expired';
        }
        if ($this->isNotStarted()) {
            return 'scheduled';
        }
        if ($this->isUsageLimitReached()) {
            return 'exhausted';
        }
        return 'active';
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'percent_off' => 'Percent Off (%)',
            'flat_off' => 'Flat Amount Off (Ks)',
            'bogo' => 'Buy 1 Get 1 Free',
            default => ucfirst($this->type),
        };
    }
}
