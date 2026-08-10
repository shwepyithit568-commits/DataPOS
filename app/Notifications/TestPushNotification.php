<?php

namespace App\Notifications;

use App\Contracts\LoggablePushNotification;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Ad-hoc push used by the admin push-management page: both the "Send Test
 * Notification" button and the custom title/body form reuse this class.
 * Synchronous (not queued) so the admin gets immediate feedback from the
 * test endpoint.
 */
class TestPushNotification extends Notification implements LoggablePushNotification
{
    public function __construct(
        public string $title = 'Test notification',
        public string $body = 'This is a test push notification.',
        public string $url = '/',
    ) {
    }

    public function logType(): string
    {
        return 'system';
    }

    public function logTitle(): string
    {
        return $this->title;
    }

    public function logBody(): string
    {
        return $this->body;
    }

    public function logUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $icon = url('/icons/icon-192.png');

        return (new WebPushMessage)
            ->title($this->title)
            ->body($this->body)
            ->icon($icon)
            ->badge($icon)
            ->data(['url' => $this->url]);
    }
}
