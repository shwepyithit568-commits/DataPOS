<?php

namespace App\Contracts;

/**
 * Lets a Web Push notification describe itself for the audit log
 * (push_notification_logs) so the title/body/url stored in the history page
 * always match what was actually sent. Implemented by every notification the
 * AdminPushNotifier dispatches.
 */
interface LoggablePushNotification
{
    /** order | payment | status | system */
    public function logType(): string;

    public function logTitle(): string;

    public function logBody(): string;

    public function logUrl(): string;
}
