<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreThemeRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'revision_number',
        'theme_config',
        'action',
        'source_revision_id',
        'actor_id',
        'created_at',
    ];

    protected $casts = [
        'revision_number' => 'integer',
        'theme_config' => 'array',
        'created_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function sourceRevision(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_revision_id');
    }
}
