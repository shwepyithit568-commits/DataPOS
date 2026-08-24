<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'name',
        'code',
        'account_type',
        'account_number',
        'account_holder',
        'opening_balance',
        'current_balance',
        'currency',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function outgoingTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'from_account_id');
    }

    public function incomingTransactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'to_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeCash($query)
    {
        return $query->where('account_type', 'cash');
    }

    public function scopeDigital($query)
    {
        return $query->whereIn('account_type', ['mobile_wallet', 'bank_account']);
    }

    public function maskedAccountNumber(): ?string
    {
        if (!$this->account_number) {
            return null;
        }

        $length = strlen($this->account_number);
        if ($length <= 4) {
            return $this->account_number;
        }

        return '•••• ' . substr($this->account_number, -4);
    }
}
