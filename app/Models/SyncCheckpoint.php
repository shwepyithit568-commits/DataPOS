<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncCheckpoint extends Model
{
    protected $table = 'sync_checkpoints';

    protected $fillable = [
        'store_id',
        'entity_type',
        'last_synced_at',
        'last_cursor',
    ];

    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
