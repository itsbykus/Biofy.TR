<?php

namespace App\Models\Concerns;

/**
 * Minimal translatable-JSON support: attributes hold {"tr": "...", "en": "..."}.
 * Admins are never forced to fill every locale; reads fall back to the other
 * locale instead of showing an empty string.
 */
trait HasTranslations
{
    public function translate(string $attribute, ?string $locale = null): ?string
    {
        $values = $this->getAttribute($attribute);

        if (! is_array($values)) {
            return $values === null ? null : (string) $values;
        }

        $locale ??= app()->getLocale();

        foreach (array_unique([$locale, 'tr', 'en']) as $candidate) {
            if (isset($values[$candidate]) && is_string($values[$candidate]) && $values[$candidate] !== '') {
                return $values[$candidate];
            }
        }

        return null;
    }

    /** @return list<string> */
    public function translateList(string $attribute, ?string $locale = null): array
    {
        $values = $this->getAttribute($attribute);
        $locale ??= app()->getLocale();

        if (! is_array($values)) {
            return [];
        }

        foreach (array_unique([$locale, 'tr', 'en']) as $candidate) {
            if (! empty($values[$candidate]) && is_array($values[$candidate])) {
                return array_values(array_filter($values[$candidate], fn ($v) => is_string($v) && trim($v) !== ''));
            }
        }

        return [];
    }
}
