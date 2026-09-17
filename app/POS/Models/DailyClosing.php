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
        'summary_snapshot',
        'total_difference',
        'explanation',
        'pending_offline_transaction_count',
        'approval_status',
        'approver_id',
        'closed_at',
        'approved_at',
        'reopened_at',
        'reopened_by',
        'reopen_reason',
        'created_by',
    ];

    protected $casts = [
        'business_date' => 'date',
        'opening_amount' => 'decimal:2',
        'expected_totals' => 'array',
        'counted_totals' => 'array',
        'differences' => 'array',
        'summary_snapshot' => 'array',
        'total_difference' => 'decimal:2',
        'closed_at' => 'datetime',
        'approved_at' => 'datetime',
        'reopened_at' => 'datetime',
    ];

    /**
     * Get the immutable summary snapshot or reconstruct legacy snapshot without ledger queries.
     *
     * @return array<string, mixed>
     */
    public function getSummarySnapshot(): array
    {
        if (!empty($this->summary_snapshot) && is_array($this->summary_snapshot)) {
            if (isset($this->summary_snapshot['metrics']) && is_array($this->summary_snapshot['metrics'])) {
                return $this->summary_snapshot['metrics'];
            }
            return $this->summary_snapshot;
        }

        return [
            'gross_sales'   => null,
            'discounts'     => null,
            'tax'           => null,
            'returns'       => null,
            'net_sales'     => null,
            'opening_cash'  => (string) $this->opening_amount,
            'cash_sales'    => null,
            'cash_refunds'  => null,
            'cash_in'       => null,
            'cash_out'      => null,
            'expected_cash' => (string) ($this->expected_totals['cash'] ?? '0.00'),
            'is_legacy'     => true,
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * The expense detail as it stood when this closing was created.
     *
     * Returns null when the closing predates detail snapshots (v1: the original
     * `version` + `metrics` shape). Callers must NOT fall back to live expenses
     * in that case — live rows are not historical evidence for an approved
     * document.
     *
     * @return list<array<string, mixed>>|null
     */
    public function frozenExpenseRows(): ?array
    {
        $snapshot = $this->summary_snapshot;

        if (! is_array($snapshot) || ! array_key_exists('expenses', $snapshot)) {
            return null;
        }

        $meta = $snapshot['expenses'];

        return is_array($meta) && isset($meta['rows']) && is_array($meta['rows'])
            ? array_values($meta['rows'])
            : null;
    }

    /**
     * True when this closing carries a real historical expense breakdown.
     */
    public function hasFrozenExpenseDetail(): bool
    {
        return $this->frozenExpenseRows() !== null;
    }

    /**
     * Snapshot detail metadata: how many rows existed at closing time, and how
     * much deducted cash-out was dropped when the bounded snapshot overflowed.
     *
     * @return array{total:int,truncated:bool,dropped_amount:string}
     */
    public function frozenExpenseMeta(): array
    {
        $snapshot = $this->summary_snapshot;
        $meta = is_array($snapshot) ? ($snapshot['expenses'] ?? null) : null;

        if (! is_array($meta)) {
            return ['total' => 0, 'truncated' => false, 'dropped_amount' => '0.00'];
        }

        return [
            'total' => (int) ($meta['total'] ?? count($meta['rows'] ?? [])),
            'truncated' => (bool) ($meta['truncated'] ?? false),
            'dropped_amount' => (string) ($meta['dropped_amount'] ?? '0.00'),
        ];
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
