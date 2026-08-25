<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EloadTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'eload_account_id',
        'user_id',
        'operator',
        'phone_number',
        'customer_name',
        'type',
        'package_name',
        'amount',
        'cost',
        'profit',
        'payment_method',
        'status',
        'ref_no',
        'notes',
        'occurred_at',
    ];

    protected $casts = [
        'amount'      => 'decimal:2',
        'cost'        => 'decimal:2',
        'profit'      => 'decimal:2',
        'occurred_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(EloadAccount::class, 'eload_account_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Operator badge color mapping.
     */
    public function operatorColor(): string
    {
        return match (strtolower($this->operator)) {
            'mpt'     => 'amber',
            'atom'    => 'sky',
            'ooredoo' => 'rose',
            'mytel'   => 'orange',
            default   => 'slate',
        };
    }

    /**
     * Type badge label.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'topup'        => 'Top-up (ဘေလ်ဖြည့်)',
            'data_pack'    => 'Data Pack (ဒေတာ)',
            'pin_code'     => 'Pin / Card (ကတ်)',
            'bill_payment' => 'Bill (ဘေလ်ပေးသွင်း)',
            default        => ucfirst($this->type),
        };
    }

    /**
     * Status badge color mapping.
     */
    public function statusColor(): string
    {
        return match ($this->status) {
            'completed' => 'emerald',
            'pending'   => 'amber',
            'failed'    => 'rose',
            'refunded'  => 'slate',
            default     => 'slate',
        };
    }
}
