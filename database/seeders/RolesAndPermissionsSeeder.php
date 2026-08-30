<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\UserOrganization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect([
            'organizations.view',
            'organizations.update',
            'organizations.delete',
            'projects.view',
            'projects.create',
            'projects.update',
            'projects.delete',
        ])->mapWithKeys(fn (string $permission): array => [
            $permission => Permission::findOrCreate($permission, 'web'),
        ]);

        collect([
            'owner' => $permissions->keys()->all(),
            'project-manager' => [
                'organizations.view',
                'projects.view',
                'projects.create',
                'projects.update',
            ],
            'member' => [
                'organizations.view',
                'projects.view',
            ],
        ])->each(function (array $rolePermissions, string $roleName): void {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleName],
                [
                    'name' => $roleName,
                    'guard_name' => 'web',
                ],
            );

            $role->syncPermissions($rolePermissions);
        });

        DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', User::class)
            ->delete();

        UserOrganization::query()
            ->with('role')
            ->get()
            ->each(function (UserOrganization $membership): void {
                if ($membership->role) {
                    $membership->syncRoles($membership->role);
                }
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
