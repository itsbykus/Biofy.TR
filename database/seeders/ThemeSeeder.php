<?php

namespace Database\Seeders;

use App\Models\Theme;
use App\Themes\ThemeRegistry;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $registry = new ThemeRegistry;
        $order = 0;

        foreach ($registry->definitions() as $key => $definition) {
            Theme::query()->firstOrCreate(['key' => $key], [
                'is_active' => true,
                'is_premium' => (bool) $definition['premium'],
                'sort_order' => $order++,
            ]);
        }

        $registry->flush();
    }
}
