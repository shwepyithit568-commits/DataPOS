<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit trail of every Web Push notification dispatched from the app
 * (new order, order status change, payment received, admin test/custom).
 * Shown on the admin "Push History" page.
 */
class PushNotificationLog extends Model
{
    public const TYPE_ORDER = 'order';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_STATUS = 'status';
    public const TYPE_SYSTEM = 'system';

    public const TYPES = [
        self::TYPE_ORDER,
        self::TYPE_PAYMENT,
        self::TYPE_STATUS,
        self::TYPE_SYSTEM,
    ];

    protected $fillable = [
        'type',
        'title',
        'body',
        'url',
        'recipient_count',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'recipient_count' => 'integer',
    ];
}
