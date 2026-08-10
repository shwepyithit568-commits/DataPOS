<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Notifiable;

/**
 * Notifiable stand-in that routes WebPush notifications to a pre-built
 * collection of PushSubscription models (e.g. "every subscriber" for the
 * admin test/custom broadcast). The webpush channel calls
 * `routeNotificationFor('WebPush')`, which must return an Eloquent
 * Collection of PushSubscription rows — this wrapper provides that.
 */
class PushSubscriberList
{
    use Notifiable;

    /**
     * @param  Collection<int, \App\Models\PushSubscription>  $subscriptions
     */
    public function __construct(public Collection $subscriptions)
    {
    }

    /**
     * @return Collection<int, \App\Models\PushSubscription>
     */
    public function routeNotificationForWebPush(): Collection
    {
        return $this->subscriptions;
    }

    /**
     * Stable identifier for notification bookkeeping (used by
     * Notification::fake() / notification logging). Not a DB key.
     */
    public function getKey(): string
    {
        return 'push-subscriber-list';
    }
}
