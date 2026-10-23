<?php

namespace App\Services\Mail;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends a notification without letting a mail outage break the user's action
 * (registration, password change...). Failures are logged without credentials.
 */
class Mailer
{
    public function send(object $notifiable, Notification $notification): bool
    {
        try {
            $notifiable->notify($notification);

            return true;
        } catch (Throwable $e) {
            Log::error('Mail delivery failed', [
                'notification' => class_basename($notification),
                'recipient_id' => $notifiable->id ?? null,
                'error' => preg_replace('/(password|pass|secret|auth)[^\s,]*/i', '[redacted]', $e->getMessage()),
            ]);

            return false;
        }
    }
}
