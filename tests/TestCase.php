<?php

namespace Tests;

use App\Models\Package;
use App\Models\Profile;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPackageAssignment;
use App\Services\Entitlements;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Users get the default (Basic) package unless a test assigns another one. */
    protected function makeUser(array $attributes = [], ?string $package = null): User
    {
        $user = User::factory()->create($attributes);

        if ($package !== null) {
            $this->assignPackage($user, $package);
        }

        return $user;
    }

    protected function makeSuperAdmin(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('key', Role::SUPERADMIN)->value('id'), ['created_at' => now()]);

        return $user->fresh();
    }

    protected function makeModerator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('key', Role::MODERATOR)->value('id'), ['created_at' => now()]);

        return $user->fresh();
    }

    protected function assignPackage(User $user, string $key): void
    {
        UserPackageAssignment::query()->create([
            'user_id' => $user->id,
            'package_id' => Package::query()->where('key', $key)->value('id'),
            'starts_at' => now()->subMinute(),
            'source' => 'admin',
        ]);

        app(Entitlements::class)->flushUser($user);
    }

    protected function makeProfile(User $user, array $attributes = [], bool $publish = false): Profile
    {
        $profile = app(ProfileService::class)->create($user, $attributes + [
            'slug' => 'p'.strtolower(\Illuminate\Support\Str::random(9)),
            'display_name' => $user->name,
        ]);

        if ($publish) {
            app(ProfileService::class)->publish($profile, $user);
        }

        return $profile->fresh();
    }
}
