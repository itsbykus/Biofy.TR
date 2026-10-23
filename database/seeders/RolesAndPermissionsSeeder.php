<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Permissions::ALL as $key => $description) {
            Permission::query()->firstOrCreate(['key' => $key], ['description' => $description]);
        }

        $superadmin = Role::query()->firstOrCreate(['key' => Role::SUPERADMIN], [
            'name' => 'Superadmin',
            'description' => 'Full platform access. Can only be granted by another superadmin.',
            'is_system' => true,
        ]);
        $superadmin->permissions()->sync(Permission::query()->pluck('id'));

        $moderator = Role::query()->firstOrCreate(['key' => Role::MODERATOR], [
            'name' => 'Moderator',
            'description' => 'Support staff: view and moderate users and pages, read messages.',
            'is_system' => true,
        ]);

        if ($moderator->wasRecentlyCreated) {
            $moderator->permissions()->sync(Permission::query()->whereIn('key', Permissions::MODERATOR)->pluck('id'));
        }
    }
}
