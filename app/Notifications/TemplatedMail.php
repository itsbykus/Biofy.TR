<?php

namespace App\Notifications;

use App\Services\Mail\EmailTemplates;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base for all e-mails: renders an admin-editable template in the recipient's
 * language into the branded layout. Sent synchronously (no queue worker needed).
 */
abstract class TemplatedMail extends Notification
{
    use Queueable;

    abstract protected function templateKey(): string;

    /** Placeholder values for the template. */
    abstract protected function variables(object $notifiable): array;

    /** Call-to-action URL rendered as a button (optional). */
    protected function actionUrl(object $notifiable): ?string
    {
        return null;
    }

    protected function actionLabel(string $locale): ?string
    {
        return __('emails.'.$this->templateKey().'.action', [], $locale);
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = method_exists($notifiable, 'preferredLocale') ? $notifiable->preferredLocale() : app()->getLocale();
        $rendered = app(EmailTemplates::class)->render($this->templateKey(), $locale, $this->variables($notifiable));
        $url = $this->actionUrl($notifiable);

        return (new MailMessage)
            ->subject($rendered['subject'])
            ->view(['mail.templated', 'mail.templated-text'], [
                'lines' => $rendered['lines'],
                'actionUrl' => $url,
                'actionLabel' => $url ? $this->actionLabel($locale) : null,
                'locale' => $locale,
            ]);
    }
}
