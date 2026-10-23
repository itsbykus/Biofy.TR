<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Every literal translation key used in code/views exists in both Turkish and English. */
class TranslationCompletenessTest extends TestCase
{
    public function test_all_used_keys_exist_in_tr_and_en(): void
    {
        $files = array_merge(File::allFiles(app_path()), File::allFiles(resource_path('views')));
        $keys = [];

        foreach ($files as $file) {
            preg_match_all("/(?:__|trans_choice|trans)\(\s*'([a-z_]+\.[A-Za-z0-9_.\-]+)'/", $file->getContents(), $matches);

            foreach ($matches[1] as $key) {
                // Keys ending with "." or "_" are prefixes completed at runtime (checked below).
                if (! str_ends_with($key, '.') && ! str_ends_with($key, '_')) {
                    $keys[$key] = $file->getRelativePathname();
                }
            }
        }

        $missing = [];

        foreach (['tr', 'en'] as $locale) {
            foreach ($keys as $key => $file) {
                if (! trans()->hasForLocale($key, $locale)) {
                    $missing[] = "[{$locale}] {$key} ({$file})";
                }
            }
        }

        $this->assertSame([], $missing, "Missing translations:\n".implode("\n", $missing));
    }

    public function test_dynamic_key_families_are_complete(): void
    {
        $families = [
            'blocks.types' => array_map(fn ($k) => $k.'.name', array_keys(app(\App\Blocks\BlockRegistry::class)->all())),
            'social.platforms' => array_keys(config('biofy.social_platforms')),
            'profile.badges' => config('biofy.badges'),
            'security.events' => array_values((new \ReflectionClass(\App\Services\SecurityLogger::class))->getConstants()),
            'themes.fields' => array_keys(\App\Themes\ThemeSettings::fields()),
            'admin.templates.names' => \App\Services\Mail\EmailTemplates::KEYS,
            'system.checks' => array_keys(app(\App\Services\System\HealthCheck::class)->full()),
        ];

        foreach (\App\Themes\ThemeSettings::fields() as $field) {
            if ($field['type'] === 'enum' && ! in_array('inter', $field['options'], true)) {
                $families['themes.options'] = array_merge($families['themes.options'] ?? [], $field['options']);
            }
        }

        foreach (\App\Http\Controllers\Admin\SettingsController::groups() as $group => $keys) {
            $families['admin.settings.groups'][] = $group;

            foreach ($keys as $key) {
                $families['admin.settings.fields'][] = str_replace('.', '_', $key);
            }
        }

        foreach (\App\Services\Mail\EmailTemplates::KEYS as $key) {
            $families['emails'][] = $key.'.subject';
            $families['emails'][] = $key.'.body';
        }

        $missing = [];

        foreach (['tr', 'en'] as $locale) {
            foreach ($families as $prefix => $suffixes) {
                foreach (array_unique($suffixes) as $suffix) {
                    if (! trans()->hasForLocale($prefix.'.'.$suffix, $locale)) {
                        $missing[] = "[{$locale}] {$prefix}.{$suffix}";
                    }
                }
            }
        }

        $this->assertSame([], $missing, "Missing translations:\n".implode("\n", $missing));
    }

    public function test_locale_files_have_identical_key_sets(): void
    {
        $flatten = function (array $array, string $prefix = '') use (&$flatten): array {
            $out = [];

            foreach ($array as $key => $value) {
                is_array($value) ? $out += $flatten($value, $prefix.$key.'.') : $out[$prefix.$key] = true;
            }

            return $out;
        };

        foreach (File::files(lang_path('tr')) as $file) {
            $en = lang_path('en/'.$file->getFilename());
            $this->assertFileExists($en);
            $trKeys = array_keys($flatten(require $file->getPathname()));
            $enKeys = array_keys($flatten(require $en));
            $this->assertSame([], array_values(array_diff($trKeys, $enKeys)), 'Only in tr/'.$file->getFilename());
            $this->assertSame([], array_values(array_diff($enKeys, $trKeys)), 'Only in en/'.$file->getFilename());
        }
    }
}
