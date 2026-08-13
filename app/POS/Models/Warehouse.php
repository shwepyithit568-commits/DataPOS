<?php

namespace App\POS\Models;

use App\Models\Store;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Warehouse = inventory location (SoT §14.2 / target-design §2.11).
 * Belongs to a store and optionally to a branch.
 */
class Warehouse extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'name',
        'code',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
