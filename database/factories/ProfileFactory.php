<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'slug' => 'p'.Str::lower(Str::random(9)),
            'display_name' => fake()->name(),
            'theme_key' => 'minimal-light',
            'theme_settings' => [],
            'language' => 'tr',
        ];
    }
}
