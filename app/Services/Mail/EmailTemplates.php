<?php

namespace App\Services\Mail;

use App\Models\EmailTemplate;
use App\Services\Settings;

/**
 * Admin-editable transactional e-mail texts (TR/EN). Templates are plain text with
 * {placeholders}; values are inserted *after* HTML-escaping so neither an admin
 * template nor user data (e.g. a display name) can inject markup.
 */
class EmailTemplates
{
    public const KEYS = ['welcome', 'verify_email', 'reset_password', 'security_alert', 'announcement', 'account_suspended', 'account_reactivated', 'deletion_requested', 'contact_received', 'test'];

    public function __construct(private readonly Settings $settings)
    {
    }

    /** @return array{subject: string, lines: list<string>} lines are already-escaped HTML-safe strings */
    public function render(string $key, string $locale, array $vars = []): array
    {
        $template = EmailTemplate::query()->where('key', $key)->where('locale', $locale)->first()
            ?? EmailTemplate::query()->where('key', $key)->where('locale', 'tr')->first();

        $subject = $template?->subject ?? (string) __('emails.'.$key.'.subject', [], $locale);
        $body = $template?->body ?? (string) __('emails.'.$key.'.body', [], $locale);

        $vars = ['site' => (string) $this->settings->get('site.name')] + $vars;

        return [
            'subject' => $this->fill($subject, $vars, false),
            'lines' => array_values(array_filter(array_map('trim', explode("\n\n", $this->fill(str_replace("\r\n", "\n", $body), $vars, true))), fn ($l) => $l !== '')),
        ];
    }

    public static function placeholders(string $key): array
    {
        return match ($key) {
            'welcome' => ['name', 'site', 'url'],
            'verify_email', 'reset_password' => ['name', 'site', 'url', 'minutes'],
            'security_alert' => ['name', 'site', 'event', 'time', 'ip'],
            'announcement' => ['name', 'site', 'message'],
            'account_suspended' => ['name', 'site', 'reason'],
            'account_reactivated', 'deletion_requested' => ['name', 'site', 'date'],
            'contact_received' => ['site', 'message', 'url'],
            default => ['site'],
        };
    }

    private function fill(string $text, array $vars, bool $escape): string
    {
        if ($escape) {
            $text = e($text);
        }

        return preg_replace_callback('/\{([a-z_]+)\}/', function ($m) use ($vars, $escape) {
            if (! array_key_exists($m[1], $vars)) {
                return $m[0];
            }

            $value = (string) $vars[$m[1]];

            return $escape ? e($value) : $value;
        }, $text);
    }
}
