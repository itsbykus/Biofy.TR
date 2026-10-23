<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Platform bootstrap data. Every seeder is idempotent (safe to re-run during
 * updates) and never overwrites values an administrator has edited.
 * No demo users or fake content are created.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            FeatureCatalogSeeder::class,
            PackageSeeder::class,
            ThemeSeeder::class,
            PageSeeder::class,
            EmailTemplateSeeder::class,
            SystemSettingsSeeder::class,
        ]);
    }
}
