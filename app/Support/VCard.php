<?php

namespace App\Support;

/** Minimal RFC 6350 vCard 3.0 writer with proper value escaping. */
final class VCard
{
    public static function build(array $data): string
    {
        $lines = ['BEGIN:VCARD', 'VERSION:3.0'];
        $name = self::escape((string) ($data['name'] ?? ''));
        $lines[] = 'FN:'.$name;
        $lines[] = 'N:'.$name.';;;;';

        if (! empty($data['title'])) {
            $lines[] = 'TITLE:'.self::escape($data['title']);
        }

        if (! empty($data['email']) && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $lines[] = 'EMAIL;TYPE=INTERNET:'.self::escape($data['email']);
        }

        if (! empty($data['phone']) && ($phone = Phone::normalize($data['phone']))) {
            $lines[] = 'TEL;TYPE=CELL:'.$phone;
        }

        if (! empty($data['url'])) {
            $lines[] = 'URL:'.self::escape($data['url']);
        }

        if (! empty($data['note'])) {
            $lines[] = 'NOTE:'.self::escape($data['note']);
        }

        $lines[] = 'END:VCARD';

        return implode("\r\n", $lines)."\r\n";
    }

    private static function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return str_replace(['\\', ',', ';', "\r\n", "\n", "\r"], ['\\\\', '\\,', '\\;', '\\n', '\\n', '\\n'], $value);
    }
}
