<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncOutboxRecord extends Model
{
    protected $table = 'sync_outbox_records';

    protected $fillable = [
        'store_id',
        'branch_id',
        'device_id',
        'record_type',
        'client_transaction_id',
        'payload',
        'status',
        'error_message',
        'retry_count',
        'created_offline_at',
        'synced_at',
    ];

    protected $casts = [
        'payload'            => 'array',
        'retry_count'        => 'integer',
        'created_offline_at' => 'datetime',
        'synced_at'          => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeSynced($query)
    {
        return $query->where('status', 'synced');
    }
}
