<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        $rolesWithPermissions = [

            'Admin' => [
                'view users',
                'create user',
                'update user',
                'delete user',

                'view',
                'create',
                'update',
                'delete',

                'view roles',
                'add permissions on roles',
                'delete permissions on roles',
                'view permissions',

                // services
                'view services',
                'create services',
                'edit services',
                'delete services',

                // hospital
                'view hospitals',
                'create hospitals',
                'edit hospitals',
                'delete hospitals',

                // hospital services
                'view hospital services',
                'create hospital services',
                'edit hospital services',
                'delete hospital services',

                //hospital users
                'view hospital users',
                'create hospital users',
                'edit hospital users',
                'delete hospital users',

                //user points
                'view user points',
                'create user points',
                'edit user points',
                'delete user points',

                // user visits
                'view user visits',
                'create user visits',
                'edit user visits',
                'delete user visits',
            ],
            'Health Facility Manager' => [
                // hospital
                'view hospitals',
                'create hospitals',
                'edit hospitals',
                'delete hospitals',

                // hospital services
                'view hospital services',
                'create hospital services',
                'edit hospital services',
                'delete hospital services',

                //hospital users
                'view hospital users',
                'create hospital users',
                'edit hospital users',
                'delete hospital users',

                //user points
                'view user points',
                'create user points',
                'edit user points',
                'delete user points',

                // user visits
                'view user visits',
                'create user visits',
                'edit user visits',
                'delete user visits',
            ],
            'Patient' => [
                'view',
                'create',
                'update',
                'delete',

            ],

        ];

        $this->createRolesAndPermissions($rolesWithPermissions);
    }

    private function createRolesAndPermissions(array $rolesWithPermissions)
    {
        foreach ($rolesWithPermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }
    }
}