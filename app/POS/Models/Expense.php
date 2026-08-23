<?php

namespace App\POS\Models;

use App\Models\Store;
use App\Models\User;
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
        'expense_category_id',
        'expense_number',
        'title',
        'amount',
        'expense_date',
        'payment_method',
        'paid_to',
        'reference_no',
        'notes',
        'attachment_path',
        'recorded_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
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
     */
    public static function generateExpenseNumber(int $storeId): string
    {
        $today = now()->format('Ymd');
        $prefix = "EXP-{$today}-";

        $lastExpense = self::where('store_id', $storeId)
            ->where('expense_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        if ($lastExpense && preg_match('/-(\d+)$/', $lastExpense->expense_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }
}
