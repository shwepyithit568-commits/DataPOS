<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipTier extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'code',
        'min_spending',
        'discount_percent',
        'point_multiplier',
        'badge_color',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'min_spending' => 'float',
        'discount_percent' => 'float',
        'point_multiplier' => 'float',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
