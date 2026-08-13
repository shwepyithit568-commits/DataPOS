<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Store-scoped supplier master data. Seeded/imported during the AlinnThit
 * pilot; goods receipts reference suppliers by name/phone today and will
 * link to this table for purchasing/payables in the Operations phase.
 */
class Supplier extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'contact_person',
        'address',
        'notes',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
