<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Cashier shift — one cashier on one register, from open to close
 * (target-design §2.10).
 */
class CashierShift extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'register_name',
        'cashier_id',
        'status',
        'opened_at',
        'opening_cash',
        'cash_sales',
        'cash_refunds',
        'cash_in',
        'cash_out',
        'expected_closing_amount',
        'actual_closing_amount',
        'difference',
        'manager_approval',
        'notes',
        'closed_at',
        'closed_by',
    ];

    protected $casts = [
        'opening_cash' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'cash_refunds' => 'decimal:2',
        'cash_in' => 'decimal:2',
        'cash_out' => 'decimal:2',
        'expected_closing_amount' => 'decimal:2',
        'actual_closing_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'manager_approval' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function cashEvents(): HasMany
    {
        return $this->hasMany(CashEvent::class, 'cashier_shift_id');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
