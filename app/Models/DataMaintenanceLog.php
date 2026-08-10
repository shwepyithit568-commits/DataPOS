<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMaintenanceLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'execution_id',
        'operation',
        'store_id',
        'record_type',
        'record_id',
        'field_name',
        'old_value',
        'new_value',
        'metadata',
        'executed_by',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
