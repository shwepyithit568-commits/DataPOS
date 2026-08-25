<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EloadAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'operator',
        'name',
        'phone_number',
        'balance',
        'discount_percent',
        'is_active',
    ];

    protected $casts = [
        'balance'          => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(EloadTransaction::class);
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
}
