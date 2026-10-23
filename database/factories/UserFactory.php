<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Test-only factory (never used by seeders in production).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => 'u'.Str::lower(Str::random(10)),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123!'),
            'remember_token' => Str::random(10),
            'locale' => 'tr',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_SUSPENDED, 'suspended_at' => now()]);
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user) {
            $role = Role::query()->firstOrCreate(['key' => Role::SUPERADMIN], ['name' => 'Superadmin', 'is_system' => true]);
            $user->roles()->attach($role->id, ['created_at' => now()]);
        });
    }
}
