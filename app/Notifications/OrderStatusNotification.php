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
 * Fired when an admin changes an order's status (e.g. confirmed / delivered).
 * Delivered to the store's managers/staff via Web Push so the team knows the
 * order progressed even when they are away from the admin panel.
 */
class OrderStatusNotification extends Notification implements ShouldQueue, LoggablePushNotification
{
    use Queueable;

    public $tries = 3;

    /** Customer-readable Burmese labels for the order statuses. */
    public const STATUS_LABELS = [
        'pending_contact' => 'ဆက်သွယ်ရန် စောင့်ဆိုင်းနေသည်',
        'confirmed' => 'အတည်ပြုပြီး',
        'delivered' => 'ပို့ဆောင်ပြီး',
        'cancelled' => 'ဖျက်သိမ်းပြီး',
    ];

    public function __construct(public Order $order, public string $status)
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
        return 'status';
    }

    public function logTitle(): string
    {
        $label = self::STATUS_LABELS[$this->status] ?? $this->status;

        return "📦 အော်ဒါ #{$this->order->order_number} သည် {$label} ဖြစ်ပါပြီ";
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
