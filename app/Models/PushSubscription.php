<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\PushSubscription as BasePushSubscription;

/**
 * Web Push subscription.
 *
 * Schema (see the migration): `endpoint` (unique), `keys` (JSON: p256dh +
 * auth), optional `user_id`. The laravel-notification-channels/webpush channel
 * reads `public_key` / `auth_token` / `content_encoding` off the model, so
 * this class bridges those attributes onto the JSON `keys` column via
 * accessors — no schema change needed.
 */
class PushSubscription extends BasePushSubscription
{
    use Notifiable;

    protected $fillable = [
        'endpoint',
        'keys',
        'user_id',
    ];

    protected $casts = [
        'keys' => 'array',
    ];

    /**
     * The user who owns this subscription (nullable for guest browsers).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * p256dh key — mapped from the JSON `keys` column for the push channel.
     */
    public function getPublicKeyAttribute(): ?string
    {
        return $this->keys['p256dh'] ?? null;
    }

    /**
     * auth token — mapped from the JSON `keys` column for the push channel.
     */
    public function getAuthTokenAttribute(): ?string
    {
        return $this->keys['auth'] ?? null;
    }

    /**
     * content_encoding — not stored; the channel defaults to aes128gcm.
     */
    public function getContentEncodingAttribute(): ?string
    {
        return null;
    }

    /**
     * Make this subscription itself notifiable through the WebPush channel.
     * The channel reads routeNotificationFor('WebPush') and expects an
     * Eloquent collection of PushSubscription rows — here it is just this one.
     */
    public function routeNotificationForWebPush(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->newCollection([$this]);
    }

    /**
     * Store (create or update by endpoint) a subscription from an API request.
     *
     * Validates nothing itself — the controller validates first — it only
     * persists the payload. Updates the existing row when the endpoint is
     * already known so re-subscribing never creates duplicates.
     */
    public static function storeFromRequest(Request $request): self
    {
        $endpoint = (string) $request->input('endpoint');
        $keys = (array) $request->input('keys', []);

        $subscription = static::firstOrNew(['endpoint' => $endpoint]);

        $subscription->fill([
            'keys' => [
                'p256dh' => $keys['p256dh'] ?? null,
                'auth' => $keys['auth'] ?? null,
            ],
            'user_id' => $request->user()?->id,
        ]);

        $subscription->save();

        return $subscription;
    }

    /**
     * Remove a subscription by its endpoint.
     */
    public static function removeSubscription(string $endpoint): bool
    {
        return (bool) static::where('endpoint', $endpoint)->delete();
    }
}
