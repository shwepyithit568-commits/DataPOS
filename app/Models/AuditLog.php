<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Generic audit trail — who did what, when (plan Phase 1 "Audit and approvals").
 *
 * Immutable by convention: rows are created, never edited or deleted.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'actor_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
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

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Convenience writer.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function write(
        ?int $storeId,
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?int $actorId = null,
        ?string $ipAddress = null,
    ): AuditLog {
        return static::create([
            'store_id' => $storeId,
            'actor_id' => $actorId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
            'created_at' => now(),
        ]);
    }

    /** Count of a given action for an entity (e.g. receipt reprints). */
    public static function countFor(string $action, string $entityType, int $entityId): int
    {
        return static::query()
            ->where('action', $action)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->count();
    }
}
