<?php

namespace App\Notifications;

use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends TemplatedMail
{
    protected function templateKey(): string
    {
        return 'verify_email';
    }

    protected function variables(object $notifiable): array
    {
        return ['name' => $notifiable->name, 'url' => $this->actionUrl($notifiable), 'minutes' => 60];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        // Signed URL is built from APP_URL (forced root URL), never from the request Host header.
        return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ]);
    }
}
