<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            /* Access Management */
            ['name' => 'Permission Index',  'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Permission Delete', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Index',  'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Create', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Update', 'group_name' => 'Access Management Permissions'],
            ['name' => 'Role Delete', 'group_name' => 'Access Management Permissions'],

            /* User Management */
            ['name' => 'User Index',  'group_name' => 'User Management Permissions'],
            ['name' => 'User Create', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Update', 'group_name' => 'User Management Permissions'],
            ['name' => 'User Delete', 'group_name' => 'User Management Permissions'],

            /* Branch Management */
            ['name' => 'Branch Index',  'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Create', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Update', 'group_name' => 'Branch Management Permissions'],
            ['name' => 'Branch Delete', 'group_name' => 'Branch Management Permissions'],

            /* Brand Management */
            ['name' => 'Brand Index',  'group_name' => 'Brand Management Permissions'],
            ['name' => 'Brand Create', 'group_name' => 'Brand Management Permissions'],
            ['name' => 'Brand Update', 'group_name' => 'Brand Management Permissions'],
            ['name' => 'Brand Delete', 'group_name' => 'Brand Management Permissions'],

            /* Container Management */
            ['name' => 'Container Index',  'group_name' => 'Container Management Permissions'],
            ['name' => 'Container Create', 'group_name' => 'Container Management Permissions'],
            ['name' => 'Container Update', 'group_name' => 'Container Management Permissions'],
            ['name' => 'Container Delete', 'group_name' => 'Container Management Permissions'],

            /* MainCategory Management */
            ['name' => 'MainCategory Index',  'group_name' => 'MainCategory Management Permissions'],
            ['name' => 'MainCategory Create', 'group_name' => 'MainCategory Management Permissions'],
            ['name' => 'MainCategory Update', 'group_name' => 'MainCategory Management Permissions'],
            ['name' => 'MainCategory Delete', 'group_name' => 'MainCategory Management Permissions'],

            /* MeasurementUnit Management */
            ['name' => 'MeasurementUnit Index',  'group_name' => 'MeasurementUnit Management Permissions'],
            ['name' => 'MeasurementUnit Create', 'group_name' => 'MeasurementUnit Management Permissions'],
            ['name' => 'MeasurementUnit Update', 'group_name' => 'MeasurementUnit Management Permissions'],
            ['name' => 'MeasurementUnit Delete', 'group_name' => 'MeasurementUnit Management Permissions'],

            /* Organization Management */
            ['name' => 'Organization Index',  'group_name' => 'Organization Management Permissions'],
            ['name' => 'Organization Create', 'group_name' => 'Organization Management Permissions'],
            ['name' => 'Organization Update', 'group_name' => 'Organization Management Permissions'],
            ['name' => 'Organization Delete', 'group_name' => 'Organization Management Permissions'],

            /* SubCategory Management */
            ['name' => 'SubCategory Index',  'group_name' => 'SubCategory Management Permissions'],
            ['name' => 'SubCategory Create', 'group_name' => 'SubCategory Management Permissions'],
            ['name' => 'SubCategory Update', 'group_name' => 'SubCategory Management Permissions'],
            ['name' => 'SubCategory Delete', 'group_name' => 'SubCategory Management Permissions'],

            /* Supplier Management */
            ['name' => 'Supplier Index',  'group_name' => 'Supplier Management Permissions'],
            ['name' => 'Supplier Create', 'group_name' => 'Supplier Management Permissions'],
            ['name' => 'Supplier Update', 'group_name' => 'Supplier Management Permissions'],
            ['name' => 'Supplier Delete', 'group_name' => 'Supplier Management Permissions'],

            /* Unit Management */
            ['name' => 'Unit Index',  'group_name' => 'Unit Management Permissions'],
            ['name' => 'Unit Create', 'group_name' => 'Unit Management Permissions'],
            ['name' => 'Unit Update', 'group_name' => 'Unit Management Permissions'],
            ['name' => 'Unit Delete', 'group_name' => 'Unit Management Permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'group_name' => $permission['group_name'],
                'guard_name' => 'api',
            ]);
        }
    }
}
