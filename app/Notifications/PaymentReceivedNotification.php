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
 * Fired when an admin marks an order as paid. Delivered to the store's
 * managers/staff via Web Push so the team can proceed with dispatch.
 */
class PaymentReceivedNotification extends Notification implements ShouldQueue, LoggablePushNotification
{
    use Queueable;

    public $tries = 3;

    public function __construct(public Order $order)
    {
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
        return 'payment';
    }

    public function logTitle(): string
    {
        return "💵 ငွေပေးချေမှု ရရှိပါပြီ — အော်ဒါ #{$this->order->order_number}";
    }

    public function logBody(): string
    {
        return 'ဝယ်သူ: ' . ($this->order->customer_name ?? '-') .
            '၊ စုစုပေါင်း: Ks ' . $this->order->effectiveAmount();
    }

    public function logUrl(): string
    {
        $store = $this->order->store;

        return $store
            ? url("/store/{$store->slug}/admin/orders/{$this->order->id}")
            : url("/admin/orders/{$this->order->id}");
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
}
