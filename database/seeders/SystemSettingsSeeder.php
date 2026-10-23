<?php

namespace Database\Seeders;

use App\Services\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = app(Settings::class);

        // Secret for the optional "URL cron" endpoint; generated per installation.
        if (! $settings->hasSecret('cron.token')) {
            $settings->set('cron.token', Str::random(40));
        }
    }
}
