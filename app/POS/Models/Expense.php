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
}
