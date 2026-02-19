<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Clear existing permissions and roles
        $tables = [
            'role_has_permissions',
            'model_has_roles',
            'model_has_permissions',
            'roles',
            'permissions',
        ];

        // DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($tables as $table) {
            DB::statement("TRUNCATE TABLE $table CASCADE");
            // DB::table($table)->truncate();
        }
        // DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create permissions
        $permissions = [
            'role-list',
            'role-create',
            'role-edit',
            'role-delete',
            'user-list',
            'user-create',
            'user-edit',
            'user-delete',
            'builder-list',
            'builder-create',
            'builder-edit',
            'builder-delete',
            'property-list',
            'property-create',
            'property-edit',
            'property-delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'sanctum');
        }

        // Create Super Admin role
        $role = Role::findOrCreate('Super Admin', 'sanctum');

        // Give all permissions to Super Admin
        $role->givePermissionTo(Permission::all());

        // Create Super Admin User
        $user = User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Admin@123'),
            ]
        );

        $user->assignRole($role);
    }
}
