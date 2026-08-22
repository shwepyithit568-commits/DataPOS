<?php

namespace App\POS\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable status-history row for a service job (SoT §16 Status History).
 */
class ServiceJobStatus extends Model
{
    protected $fillable = [
        'service_job_id',
        'status',
        'note',
        'changed_by',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function changer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
