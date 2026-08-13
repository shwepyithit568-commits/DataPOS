<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Branch daily closing document (SoT §18).
 *
 * Expected/counted/differences are stored as JSON at closing time (immutable
 * snapshot). Approval moves pending → approved with approver + timestamp.
 */
class DailyClosing extends Model
{
    protected $fillable = [
        'store_id',
        'branch_id',
        'business_date',
        'closing_user_id',
        'opening_amount',
        'expected_totals',
        'counted_totals',
        'differences',
        'total_difference',
        'explanation',
        'pending_offline_transaction_count',
        'approval_status',
        'approver_id',
        'closed_at',
        'approved_at',
        'created_by',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opening_amount' => 'decimal:2',
        'expected_totals' => 'array',
        'counted_totals' => 'array',
        'differences' => 'array',
        'total_difference' => 'decimal:2',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function closingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closing_user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /** Drawer/collection methods that get counted (credit is info only). */
    public static function countedMethods(): array
    {
        return ['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr'];
    }

    /** All expected methods, including credit (receivable info). */
    public static function expectedMethods(): array
    {
        return ['cash', 'kpay', 'wavepay', 'cb_pay', 'mmqr', 'credit'];
    }
}
