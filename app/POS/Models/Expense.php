<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
use App\POS\Services\DocumentSequenceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $table = 'expenses';

    protected $fillable = [
        'store_id',
        'cashier_shift_id',
        'expense_category_id',
        'expense_number',
        'client_transaction_id',
        'request_fingerprint',
        'title',
        'amount',
        'status',
        'expense_date',
        'payment_method',
        'payment_source',
        'paid_to',
        'reference_no',
        'notes',
        'attachment_path',
        'recorded_by',
    ];

    public const SOURCE_DRAWER = 'drawer';
    public const SOURCE_SAFE = 'safe';
    public const SOURCE_PETTY_CASH = 'petty_cash';
    public const SOURCE_BANK = 'bank';
    public const SOURCE_OTHER = 'other';

    public const PAYMENT_SOURCES = [
        self::SOURCE_DRAWER     => 'Cash Drawer (ကောင်တာငွေအံဆွဲ)',
        self::SOURCE_SAFE       => 'Safe (သိုလှောင်သေတ္တာ)',
        self::SOURCE_PETTY_CASH => 'Petty Cash (အသေးသုံးငွေ)',
        self::SOURCE_BANK       => 'Bank / Account (ဘဏ်အကောင့်)',
        self::SOURCE_OTHER      => 'Other (အခြား)',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(CashierShift::class, 'cashier_shift_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if (! empty($from)) {
            $query->whereDate('expense_date', '>=', $from);
        }

        if (! empty($to)) {
            $query->whereDate('expense_date', '<=', $to);
        }

        return $query;
    }

    /**
     * Generate sequential, store-scoped expense number: EXP-YYYYMMDD-0001
     *
     * Issued from the shared document-sequence table under a row lock, so two
     * simultaneous expenses can never be handed the same number.
     */
    public static function generateExpenseNumber(int $storeId): string
    {
        return app(DocumentSequenceService::class)->nextNumber(
            Store::findOrFail($storeId),
            'expense'
        );
    }

    /**
     * Can this expense still move money in its drawer/shift scope?
     *
     * Only a paid cash expense explicitly sourced from the drawer is deducted
     * (see ExpenseCashAttribution). Unpaid/void rows and every other source
     * never reduce a drawer.
     */
    public function affectsDrawer(): bool
    {
        return strtolower((string) ($this->status ?? 'paid')) === 'paid'
            && strtolower((string) $this->payment_method) === 'cash'
            && $this->payment_source === self::SOURCE_DRAWER
            && $this->cashier_shift_id !== null;
    }

    /**
     * The shift whose already-closed drawer math includes (or excluded) this
     * expense. Returns null when nothing was ever deducted.
     */
    public function deductionShift(): ?CashierShift
    {
        if (! $this->affectsDrawer()) {
            return null;
        }

        return $this->relationLoaded('shift')
            ? $this->shift
            : CashierShift::find($this->cashier_shift_id);
    }

    /**
     * True when editing this row would silently rewrite a drawer that has
     * already been counted and closed. Such a change must go through a reversal
     * or an audited adjustment, not through a form edit.
     */
    public function isLockedByClosedShift(): bool
    {
        return $this->deductionShift()?->status === 'closed';
    }
}
