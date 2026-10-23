<?php

namespace App\Notifications;

/**
 * Welcome, security alert, announcement, account status and test mails share the
 * same shape: a template key, variables and an optional action URL.
 */
class GenericTemplatedNotification extends TemplatedMail
{
    public function __construct(
        private readonly string $key,
        private readonly array $vars = [],
        private readonly ?string $url = null,
    ) {
    }

    protected function templateKey(): string
    {
        return $this->key;
    }

    protected function variables(object $notifiable): array
    {
        return ['name' => $notifiable->name ?? ''] + $this->vars;
    }

    protected function actionUrl(object $notifiable): ?string
    {
        return $this->url;
    }
}
