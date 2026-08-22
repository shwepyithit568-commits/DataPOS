<?php

namespace App\POS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable payment row for a service job (SoT §16 Payments).
 */
class ServiceJobPayment extends Model
{
    protected $fillable = [
        'service_job_id',
        'method',
        'amount',
        'reference',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
