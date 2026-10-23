<?php

namespace App\Notifications;

class ResetPasswordNotification extends TemplatedMail
{
    public function __construct(private readonly string $token)
    {
    }

    protected function templateKey(): string
    {
        return 'reset_password';
    }

    protected function variables(object $notifiable): array
    {
        return ['name' => $notifiable->name, 'url' => $this->actionUrl($notifiable), 'minutes' => config('auth.passwords.users.expire', 60)];
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
    }
}
