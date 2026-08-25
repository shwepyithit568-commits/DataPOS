<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'user_id',
        'business_name',
        'phone',
        'address',
        'status',
        'notes',
        'admin_note',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
