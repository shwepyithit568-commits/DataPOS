<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Currency extends Model
{
    protected $fillable = [
        'store_id',
        'code',
        'name',
        'symbol',
        'exchange_rate',
        'is_base',
        'is_active',
        'last_updated_at',
    ];

    protected $casts = [
        'exchange_rate' => 'float',
        'is_base' => 'boolean',
        'is_active' => 'boolean',
        'last_updated_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBase($query)
    {
        return $query->where('is_base', true);
    }
}
