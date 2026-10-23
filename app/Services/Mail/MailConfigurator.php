<?php

namespace App\Services\Mail;

use App\Services\Settings;
use Illuminate\Support\Facades\Mail;

/** Applies SMTP settings stored in the database to Laravel's mail config at runtime. */
class MailConfigurator
{
    public function __construct(private readonly Settings $settings)
    {
    }

    public function apply(): void
    {
        $mailer = (string) $this->settings->get('mail.mailer');

        if (app()->environment('testing') || ! in_array($mailer, ['smtp', 'sendmail', 'log'], true)) {
            return;
        }

        $fromAddress = (string) $this->settings->get('mail.from_address');

        config([
            'mail.default' => $mailer,
            'mail.from.address' => $fromAddress !== '' ? $fromAddress : config('mail.from.address'),
            'mail.from.name' => (string) ($this->settings->get('mail.from_name') ?: $this->settings->get('site.name')),
        ]);

        if ($mailer === 'smtp') {
            $encryption = (string) $this->settings->get('mail.encryption');

            config([
                'mail.mailers.smtp.host' => (string) $this->settings->get('mail.host'),
                'mail.mailers.smtp.port' => $this->settings->int('mail.port'),
                'mail.mailers.smtp.username' => (string) $this->settings->get('mail.username') ?: null,
                'mail.mailers.smtp.password' => $this->settings->get('mail.password'),
                // "ssl" = implicit TLS (smtps, usually 465); "tls" = STARTTLS (usually 587).
                'mail.mailers.smtp.scheme' => $encryption === 'ssl' ? 'smtps' : 'smtp',
                // Passed to Symfony's DSN: "tls" requires STARTTLS, "none" disables opportunistic TLS.
                'mail.mailers.smtp.auto_tls' => $encryption !== 'none',
                'mail.mailers.smtp.require_tls' => $encryption === 'tls',
                'mail.mailers.smtp.timeout' => 15,
            ]);
        }

        // Drop any mailer instance resolved with the previous configuration.
        Mail::purge($mailer);
    }
}
