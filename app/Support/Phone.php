<?php

namespace App\Support;

final class Phone
{
    /** Normalises to "+<digits>" (E.164-like, 7-15 digits). A leading 0 is read as a Turkish national number. */
    public static function normalize(?string $input): ?string
    {
        $raw = trim((string) $input);

        if ($raw === '' || ! preg_match('/^\+?[0-9 ().-]{5,25}$/', $raw)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);

        if (! str_starts_with($raw, '+')) {
            if (str_starts_with($digits, '00')) {
                $digits = substr($digits, 2);
            } elseif (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $digits = '90'.substr($digits, 1);
            }
        }

        return strlen($digits) >= 7 && strlen($digits) <= 15 ? '+'.$digits : null;
    }
}
