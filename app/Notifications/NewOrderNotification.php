<?php

namespace App\Notifications;

use App\Contracts\LoggablePushNotification;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Fired when a new order is created. Delivered to the store admin(s) via Web
 * Push (browser notifications). The `url` is embedded in the message data so
 * the client-side service worker can open the admin order page on click.
 *
 * Queued (ShouldQueue) so order creation is never slowed by the webpush HTTP
 * delivery. Dispatch sites route through App\Support\AdminPushNotifier, which
 * dedupes the event and writes the audit log row.
 */
class NewOrderNotification extends Notification implements ShouldQueue, LoggablePushNotification
{
    use Queueable;

    public $tries = 3;

    public function __construct(public Order $order)
    {
        // Only queue after the surrounding DB transaction commits.
        $this->afterCommit = true;
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function logType(): string
    {
        return 'order';
    }

    public function logTitle(): string
    {
        return "🆕 အော်ဒါ #{$this->order->order_number}";
    }

    public function logBody(): string
    {
        // Total pieces across line items (2 rows of qty 3 → "6").
        $itemCount = $this->order->items->sum('quantity');

        return 'ဝယ်သူ: ' . ($this->order->customer_name ?? '-') .
            '၊ စုစုပေါင်း: Ks ' . $this->order->effectiveAmount() .
            '၊ ပစ္စည်းအရေအတွက်: ' . $itemCount;
    }

    public function logUrl(): string
    {
        return $this->adminOrderUrl();
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->logTitle())
            ->body($this->logBody())
            ->icon(url('/icons/icon-192.png'))
            ->badge(url('/icons/badge-72.png'))
            ->data(['url' => $this->logUrl()]);
    }

    protected function adminOrderUrl(): string
    {
        $store = $this->order->store;

        return $store
            ? url("/store/{$store->slug}/admin/orders/{$this->order->id}")
            : url("/admin/orders/{$this->order->id}");
    }
}
