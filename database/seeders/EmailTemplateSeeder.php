<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Services\Mail\EmailTemplates;
use Illuminate\Database\Seeder;

/** Copies the default TR/EN texts from lang/ so they are editable in the admin panel. */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (EmailTemplates::KEYS as $key) {
            foreach (config('biofy.locales') as $locale) {
                EmailTemplate::query()->firstOrCreate(['key' => $key, 'locale' => $locale], [
                    'subject' => __('emails.'.$key.'.subject', [], $locale),
                    'body' => __('emails.'.$key.'.body', [], $locale),
                    'is_customized' => false,
                ]);
            }
        }
    }
}
